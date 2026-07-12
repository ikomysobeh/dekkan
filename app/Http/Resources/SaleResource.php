<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'date_time'   => optional($this->date_time)->toIso8601String(),
            'quantity'    => (int) $this->quantity,
            'total_price' => (float) $this->total_price,
            'product'     => $this->whenLoaded('product', fn () => [
                'id'      => $this->product->id,
                'name'    => $this->product->name,
                'barcode' => $this->product->barcode,
            ]),
            'user'        => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
        ];
    }
}
