<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Product;
use App\Models\PaymentReceipt;
use App\Http\Controllers\Controller;

class DashboardApiController extends Controller
{
    use ApiResponses;

    /** GET /api/dashboard — headline numbers for the app home screen. */
    public function index()
    {
        $todaySales = (float) Sale::whereDate('date_time', today())->sum('total_price');
        $lowStock   = Product::whereColumn('stock_quantity', '<', 'quantity_alert')->count();
        $receipts   = (float) PaymentReceipt::where('type', 'receipt')->sum('amount');
        $payments   = (float) PaymentReceipt::where('type', 'payment')->sum('amount');

        return $this->item([
            'today_sales'     => $todaySales,
            'low_stock_count' => $lowStock,
            'cash_balance'    => $receipts - $payments,
        ]);
    }
}
