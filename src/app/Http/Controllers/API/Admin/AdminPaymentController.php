<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Traits\ApiResponse;

class AdminPaymentController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $payments = Payment::with([
            'donationRequest:id,blood_group,district',
            'payer:id,name,email',
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->success([
            'payments'     => $payments->items(),
            'current_page' => $payments->currentPage(),
            'last_page'    => $payments->lastPage(),
            'total'        => $payments->total(),
        ], 'Payments retrieved');
    }

    public function show($id)
    {
        $payment = Payment::with([
            'donationRequest:id,blood_group,quantity,district,status',
            'payer:id,name,email',
        ])->find($id);

        if (!$payment) return $this->error('Payment not found', 404);

        return $this->success($payment, 'Payment details retrieved');
    }
}
