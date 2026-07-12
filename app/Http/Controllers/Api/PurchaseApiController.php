<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use Illuminate\Support\Facades\DB;

class PurchaseApiController extends Controller
{
    use ApiResponses;

    /** GET /api/purchases — paginated list. */
    public function index()
    {
        $purchases = Purchase::with(['product', 'user'])->latest()->paginate(10);
        return $this->paginated($purchases, PurchaseResource::collection($purchases));
    }

    /** GET /api/purchases/{id}. */
    public function show(int $id)
    {
        $purchase = Purchase::with(['product', 'user'])->find($id);
        if (! $purchase) {
            return $this->message('عملية الشراء غير موجودة', 404, false);
        }
        return $this->item(new PurchaseResource($purchase));
    }

    /**
     * POST /api/purchases — create (one row per line item).
     * { date?, products:[{ product_id, quantity, purchase_price, selling_price }] }
     * Increments stock; transaction (mirrors web PurchaseController@store).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'                       => 'nullable|date',
            'products'                   => 'required|array|min:1',
            'products.*.product_id'      => 'required|integer|exists:products,id',
            'products.*.quantity'        => 'required|integer|min:1',
            'products.*.purchase_price'  => 'required|numeric|min:0',
            'products.*.selling_price'   => 'required|numeric|min:0',
        ]);

        $date = $data['date'] ?? now();

        DB::beginTransaction();
        try {
            $user    = $request->user();
            $created = [];

            foreach ($data['products'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                $purchase = Purchase::create([
                    'user_id'        => $user->id,
                    'date'           => $date,
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'selling_price'  => $item['selling_price'],
                ]);

                $product->stock_quantity += $item['quantity'];
                $product->save();

                $created[] = [
                    'purchase_id' => $purchase->id,
                    'product_id'  => $product->id,
                    'name'        => $product->name,
                    'quantity'    => $item['quantity'],
                    'stock_now'   => $product->stock_quantity,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل عملية الشراء بنجاح',
                'lines'   => $created,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->message('فشل في تسجيل عملية الشراء: ' . $e->getMessage(), 422, false);
        }
    }

    /**
     * PUT /api/purchases/{id} — update one purchase line (mirrors web update).
     * Adjusts stock: revert old quantity, apply new.
     */
    public function update(Request $request, int $id)
    {
        $purchase = Purchase::find($id);
        if (! $purchase) {
            return $this->message('عملية الشراء غير موجودة', 404, false);
        }

        $data = $request->validate([
            'date'           => 'required|date',
            'product_id'     => 'required|integer|exists:products,id',
            'quantity'       => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Revert old quantity from the previously-purchased product.
            $oldProduct = Product::find($purchase->product_id);
            if ($oldProduct) {
                $oldProduct->stock_quantity -= $purchase->quantity;
                $oldProduct->save();
            }

            // Apply new quantity to the (possibly new) product.
            $newProduct = Product::findOrFail($data['product_id']);
            $newProduct->stock_quantity += $data['quantity'];
            $newProduct->save();

            $purchase->update($data);

            DB::commit();
            return $this->item(new PurchaseResource($purchase->load(['product', 'user'])));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->message('فشل في تحديث عملية الشراء: ' . $e->getMessage(), 422, false);
        }
    }

    /**
     * DELETE /api/purchases/{id}.
     * B4 fix: remove the purchased quantity from stock before deleting.
     */
    public function destroy(int $id)
    {
        $purchase = Purchase::find($id);
        if (! $purchase) {
            return $this->message('عملية الشراء غير موجودة', 404, false);
        }

        DB::transaction(function () use ($purchase) {
            $product = Product::find($purchase->product_id);
            if ($product) {
                $product->stock_quantity -= $purchase->quantity;
                $product->save();
            }
            $purchase->delete();
        });

        return $this->message('تم الحذف بنجاح');
    }
}
