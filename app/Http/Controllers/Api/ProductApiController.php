<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Storage;

class ProductApiController extends Controller
{
    use ApiResponses;

    /** GET /api/products — paginated list. */
    public function index()
    {
        $products = Product::with('latestPurchase')->latest()->paginate(10);
        return $this->paginated($products, ProductResource::collection($products));
    }

    /** GET /api/products/alerts — low-stock products. */
    public function alerts()
    {
        $products = Product::with('latestPurchase')
            ->whereColumn('stock_quantity', '<', 'quantity_alert')
            ->get();

        return $this->item(ProductResource::collection($products));
    }

    /** GET /api/products/search?query= — by name or exact barcode. */
    public function search(Request $request)
    {
        $query = trim($request->get('query', ''));

        $products = Product::with('latestPurchase')
            ->where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('barcode', $query)
            ->limit(20)
            ->get();

        return $this->item(ProductResource::collection($products));
    }

    /** GET /api/products/by-barcode/{barcode}. */
    public function byBarcode(string $barcode)
    {
        $product = Product::with('latestPurchase')->where('barcode', $barcode)->first();

        if (! $product) {
            return $this->message('المنتج غير موجود', 404, false);
        }

        return $this->item(new ProductResource($product));
    }

    /** GET /api/products/{id}. */
    public function show(int $id)
    {
        $product = Product::with('latestPurchase')->find($id);

        if (! $product) {
            return $this->message('المنتج غير موجود', 404, false);
        }

        return $this->item(new ProductResource($product));
    }

    /** POST /api/products — create (multipart for the image). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'barcode'        => 'nullable|string|max:255|unique:products,barcode',
            'category'       => 'nullable|string|max:255',
            'quantity_alert' => 'required|integer|min:0',
            'min_order'      => 'required|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'image'          => 'nullable|image|max:5120',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'user_id'        => $request->user()->id,
            'name'           => $data['name'],
            'barcode'        => $data['barcode'] ?? null,
            'category'       => $data['category'] ?? null,
            'image_url'      => $imageUrl,
            'quantity_alert' => $data['quantity_alert'],
            'min_order'      => $data['min_order'],
            'stock_quantity' => $data['stock_quantity'] ?? 0,
        ]);

        return $this->item(new ProductResource($product->load('latestPurchase')), 201);
    }

    /**
     * POST /api/products/{id} — update (multipart).
     * B3 fix: barcode and category are editable here (the web version ignores them).
     */
    public function update(Request $request, int $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return $this->message('المنتج غير موجود', 404, false);
        }

        $data = $request->validate([
            'name'           => 'sometimes|required|string|max:255',
            'barcode'        => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($product->id)],
            'category'       => 'nullable|string|max:255',
            'quantity_alert' => 'sometimes|required|integer|min:0',
            'min_order'      => 'sometimes|required|integer|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'image'          => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $product->image_url = $request->file('image')->store('products', 'public');
        }

        // Only overwrite fields that were actually sent.
        $product->fill($request->only([
            'name', 'barcode', 'category', 'quantity_alert', 'min_order', 'stock_quantity',
        ]));
        $product->save();

        return $this->item(new ProductResource($product->load('latestPurchase')));
    }

    /** DELETE /api/products/{id}. */
    public function destroy(int $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return $this->message('المنتج غير موجود', 404, false);
        }

        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }
        $product->delete();

        return $this->message('تم الحذف بنجاح');
    }
}
