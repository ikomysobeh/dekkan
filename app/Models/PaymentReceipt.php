<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentReceipt extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'type', 'amount', 'notes'];

    protected $casts = [
        'date' => 'datetime',
        'type' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
