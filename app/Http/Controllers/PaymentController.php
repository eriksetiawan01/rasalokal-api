<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['order', 'user:id,name'])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List of payments',
            'data' => $payments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id'        => 'required|exists:orders,id',
            'amount_paid'     => 'required|numeric|min:0',
            'payment_method'  => 'required|in:cash,qris'
        ]);

        return DB::transaction(function () use ($request) {

            $order = Order::findOrFail($request->order_id);

            //Cek apakah order sudah pernah dibayar
            if ($order->payments()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already has a payment.'
                ], 400);
            }

            // Hitung total & kembalian
            $totalAmount = $order->total_amount;
            $change = max($request->amount_paid - $totalAmount, 0);

            // Generate kode pembayaran unik
            $kode = 'PMT-' . now()->format('YmdHis') . '-' . rand(1000, 9999);

            // Buat payment
            $payment = Payment::create([
                'order_id'        => $order->id,
                'user_id'         => Auth::id(),
                'kode_pembayaran' => $kode,
                'payment_method'  => $request->payment_method,
                'total_amount'    => $totalAmount,
                'amount_paid'     => $request->amount_paid,
                'change_amount'   => $change,
                'status'          => 'success',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $payment
            ]);
        });
    }

    public function show(string $id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment detail fetched',
            'data' => $payment
        ]);
    }

    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        $request->validate([
            'payment_method' => 'in:cash,qris',
            'status'         => 'in:success,failed'
        ]);

        $payment->update($request->only(['payment_method', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => $payment
        ]);
    }

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
