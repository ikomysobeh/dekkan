<?php

namespace App\Http\Controllers\Api;

use App\Models\PaymentReceipt;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentReceiptResource;

class PaymentReceiptApiController extends Controller
{
    use ApiResponses;

    /** GET /api/payment-receipts — paginated list. */
    public function index()
    {
        $receipts = PaymentReceipt::with('user')->latest()->paginate(10);
        return $this->paginated($receipts, PaymentReceiptResource::collection($receipts));
    }

    /** GET /api/payment-receipts/{id}. */
    public function show(int $id)
    {
        $receipt = PaymentReceipt::with('user')->find($id);
        if (! $receipt) {
            return $this->message('الإيصال غير موجود', 404, false);
        }
        return $this->item(new PaymentReceiptResource($receipt));
    }

    /** POST /api/payment-receipts — create. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'   => 'required|date',
            'type'   => 'required|in:payment,receipt',
            'amount' => 'required|numeric|min:0',
            'notes'  => 'nullable|string|max:255',
        ]);

        $data['user_id'] = $request->user()->id;
        $receipt = PaymentReceipt::create($data);

        return $this->item(new PaymentReceiptResource($receipt), 201);
    }

    /**
     * PUT /api/payment-receipts/{id} — update.
     * B1 fix: use `notes` (the real column) so the note text actually saves.
     */
    public function update(Request $request, int $id)
    {
        $receipt = PaymentReceipt::find($id);
        if (! $receipt) {
            return $this->message('الإيصال غير موجود', 404, false);
        }

        $data = $request->validate([
            'date'   => 'required|date',
            'type'   => 'required|in:payment,receipt',
            'amount' => 'required|numeric|min:0',
            'notes'  => 'nullable|string|max:255',
        ]);

        $receipt->update($data);

        return $this->item(new PaymentReceiptResource($receipt));
    }

    /** DELETE /api/payment-receipts/{id}. */
    public function destroy(int $id)
    {
        $receipt = PaymentReceipt::find($id);
        if (! $receipt) {
            return $this->message('الإيصال غير موجود', 404, false);
        }

        $receipt->delete();
        return $this->message('تم الحذف بنجاح');
    }
}
