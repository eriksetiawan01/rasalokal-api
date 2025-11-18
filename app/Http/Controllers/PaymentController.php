<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index()
    {
        $payments = Payment::with('order')->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List of payments',
            'data' => $payments
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,qris'
        ]);

        return DB::transaction(function () use ($request) {

            $order = Order::findOrFail($request->order_id);

            // Prevent duplicate payment
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order has already been paid.'
                ], 400);
            }

            // Calculate change
            $change = $request->amount_paid - $order->total_amount;

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'payment_method' => $request->payment_method,
                'amount_paid' => $request->amount_paid,
                'change_amount' => max($change, 0),
                'status' => 'success'
            ]);

            // Update order status
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $payment
            ]);
        });
    }

    /**
     * Display payment detail.
     */
    public function show(string $id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment detail fetched',
            'data' => $payment
        ]);
    }

    /**
     * Update payment (rarely needed).
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        $request->validate([
            'payment_method' => 'in:cash,qris',
            'status' => 'in:success,failed,canceled'
        ]);

        $payment->update($request->only(['payment_method', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => $payment
        ]);
    }

    /**
     * Delete payment (only for admin)
     */
    public function destroy(string $id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully'
        ]);
    }
}
