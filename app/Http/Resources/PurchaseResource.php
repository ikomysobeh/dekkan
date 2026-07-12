<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'date'           => optional($this->date)->toIso8601String(),
            'quantity'       => (int) $this->quantity,
            'purchase_price' => (float) $this->purchase_price,
            'selling_price'  => (float) $this->selling_price,
            'product'        => $this->whenLoaded('product', fn () => [
                'id'      => $this->product->id,
                'name'    => $this->product->name,
                'barcode' => $this->product->barcode,
            ]),
            'user'           => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
        ];
    }
}
