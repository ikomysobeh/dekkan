<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use Illuminate\Support\Facades\DB;

class SaleApiController extends Controller
{
    use ApiResponses;

    /** GET /api/sales — paginated list. */
    public function index()
    {
        $sales = Sale::with(['product', 'user'])->latest()->paginate(10);
        return $this->paginated($sales, SaleResource::collection($sales));
    }

    /** GET /api/sales/{id}. */
    public function show(int $id)
    {
        $sale = Sale::with(['product', 'user'])->find($id);
        if (! $sale) {
            return $this->message('عملية البيع غير موجودة', 404, false);
        }
        return $this->item(new SaleResource($sale));
    }

    /**
     * POST /api/sales — create a sale (one row per line item).
     * { date_time?, products:[{ product_id, quantity, selling_price? }] }
     * Decrements stock; whole thing in a transaction (mirrors web SaleController@store).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date_time'                => 'nullable|date',
            'products'                 => 'required|array|min:1',
            'products.*.product_id'    => 'required|integer|exists:products,id',
            'products.*.quantity'      => 'required|integer|min:1',
            'products.*.selling_price' => 'nullable|numeric|min:0',
        ]);

        $dateTime = $data['date_time'] ?? now();

        DB::beginTransaction();
        try {
            $user       = $request->user();
            $created    = [];
            $grandTotal = 0;

            foreach ($data['products'] as $item) {
                $product = Product::with('latestPurchase')->findOrFail($item['product_id']);

                $sellingPrice = $item['selling_price']
                    ?? ($product->latestPurchase->selling_price ?? 0);
                $totalPrice   = $sellingPrice * $item['quantity'];

                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception('المخزون غير كافٍ للمنتج: ' . $product->name);
                }

                $sale = Sale::create([
                    'user_id'       => $user->id,
                    'date_time'     => $dateTime,
                    'product_id'    => $product->id,
                    'quantity'      => $item['quantity'],
                    'selling_price' => $sellingPrice, // dropped by DB (no column) — kept for parity
                    'total_price'   => $totalPrice,
                ]);

                $product->stock_quantity -= $item['quantity'];
                $product->save();

                $grandTotal += $totalPrice;
                $created[]   = [
                    'sale_id'       => $sale->id,
                    'product_id'    => $product->id,
                    'name'          => $product->name,
                    'quantity'      => $item['quantity'],
                    'selling_price' => (float) $sellingPrice,
                    'total_price'   => (float) $totalPrice,
                    'stock_left'    => $product->stock_quantity,
                ];
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => 'تم تسجيل عملية البيع بنجاح',
                'grand_total' => (float) $grandTotal,
                'lines'       => $created,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->message('فشل في تسجيل عملية البيع: ' . $e->getMessage(), 422, false);
        }
    }

    /**
     * PUT /api/sales/{id} — update one sale line (mirrors web update).
     * Adjusts stock: revert old quantity, apply new. Price re-derived from latestPurchase.
     */
    public function update(Request $request, int $id)
    {
        $sale = Sale::find($id);
        if (! $sale) {
            return $this->message('عملية البيع غير موجودة', 404, false);
        }

        $data = $request->validate([
            'date_time'  => 'required|date',
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Revert stock on the previously-sold product.
            $oldProduct = Product::find($sale->product_id);
            if ($oldProduct) {
                $oldProduct->stock_quantity += $sale->quantity;
                $oldProduct->save();
            }

            // Apply stock on the (possibly new) product.
            $newProduct = Product::with('latestPurchase')->findOrFail($data['product_id']);
            if ($newProduct->stock_quantity < $data['quantity']) {
                throw new \Exception('المخزون غير كافٍ للمنتج: ' . $newProduct->name);
            }

            $sellingPrice = $newProduct->latestPurchase->selling_price ?? 0;
            $totalPrice   = $sellingPrice * $data['quantity'];

            $newProduct->stock_quantity -= $data['quantity'];
            $newProduct->save();

            $sale->update([
                'date_time'     => $data['date_time'],
                'product_id'    => $data['product_id'],
                'quantity'      => $data['quantity'],
                'selling_price' => $sellingPrice,
                'total_price'   => $totalPrice,
            ]);

            DB::commit();
            return $this->item(new SaleResource($sale->load(['product', 'user'])));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->message('فشل في تحديث عملية البيع: ' . $e->getMessage(), 422, false);
        }
    }

    /**
     * DELETE /api/sales/{id}.
     * B4 fix: restore the sold quantity back to stock before deleting.
     */
    public function destroy(int $id)
    {
        $sale = Sale::find($id);
        if (! $sale) {
            return $this->message('عملية البيع غير موجودة', 404, false);
        }

        DB::transaction(function () use ($sale) {
            $product = Product::find($sale->product_id);
            if ($product) {
                $product->stock_quantity += $sale->quantity;
                $product->save();
            }
            $sale->delete();
        });

        return $this->message('تم الحذف بنجاح');
    }
}
