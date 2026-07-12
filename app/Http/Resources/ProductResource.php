<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'barcode'        => $this->barcode,
            'category'       => $this->category,
            'stock_quantity' => (int) $this->stock_quantity,
            'quantity_alert' => (int) $this->quantity_alert,
            'min_order'      => (int) $this->min_order,
            'image_url'      => $this->image_url ? asset('storage/' . $this->image_url) : null,
            'selling_price'  => (float) ($this->latestPurchase->selling_price ?? 0),
            'low_stock'      => (int) $this->stock_quantity < (int) $this->quantity_alert,
        ];
    }
}
