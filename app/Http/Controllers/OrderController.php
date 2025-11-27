<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('orderItems.menu')->latest()->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No orders found',
                'data' => []
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'List of orders',
            'data' => $orders
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name'   => 'required|string',
            'order_type'      => 'required|in:dine_in,take_away',
            'items'           => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity'=> 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'data' => $validator->errors()
            ], 422);
        }

        $orderNumber = 'ORD-' . strtoupper(uniqid());
        $order = null;

        try {
            DB::transaction(function() use ($request, $orderNumber, &$order) {
                $menuIds = collect($request->items)->pluck('menu_id');
                $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');

                $total = 0;
                $orderItemsPayload = [];

                foreach ($request->items as $item) {
                    $menu = $menus[$item['menu_id']];

                    if ($menu->stock < $item['quantity']) {
                        throw new \Exception("Stok menu '{$menu->name}' tidak mencukupi.");
                    }

                    $subtotal = $menu->price * $item['quantity'];
                    $total += $subtotal;

                    $orderItemsPayload[] = [
                        'menu_id'  => $menu->id,
                        'quantity' => $item['quantity'],
                        'price'    => $menu->price,
                        'subtotal' => $subtotal,
                    ];

                    // update stock
                    $menu->stock -= $item['quantity'];
                    $menu->save();
                }

                $order = Order::create([
                    'order_number'   => $orderNumber,
                    'customer_name'  => $request->customer_name,
                    'table_number'   => $request->table_number,
                    'order_type'     => $request->order_type,
                    'total_amount'   => $total,
                    'status'         => 'pending',
                    'note'           => $request->note,
                ]);

                $order->orderItems()->createMany($orderItemsPayload);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order creation failed'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order->load('orderItems.menu')
        ], 201);
    }

    public function show(string $id)
    {
        $order = Order::with('orderItems.menu', 'payments')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order details fetched',
            'data' => $order
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $order = Order::with('orderItems.menu')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // VALIDASI
        $validator = Validator::make($request->all(), [
            'customer_name' => 'string',
            'table_number' => 'nullable|integer',
            'order_type' => 'in:dine_in,take_away',
            'note' => 'nullable|string',
            'status' => 'in:pending,processing,completed,cancelled',

            'items' => 'nullable|array|min:1',
            'items.*.menu_id' => 'required_with:items|exists:menus,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'data' => $validator->errors()
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $order) {

                $total = 0;

                if ($request->has('items')) {

                    // Kembalikan stok lama
                    foreach ($order->orderItems as $old) {
                        Menu::where('id', $old->menu_id)
                            ->increment('stock', $old->quantity);
                    }

                    // Hapus order_items lama
                    $order->orderItems()->delete();

                    // Ambil menu baru
                    $menuIds = collect($request->items)->pluck('menu_id');
                    $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');

                    $payload = [];

                    foreach ($request->items as $item) {
                        $menu = $menus[$item['menu_id']];

                        if ($menu->stock < $item['quantity']) {
                            throw new \Exception("Stok menu '{$menu->name}' tidak mencukupi.");
                        }

                        $subtotal = $menu->price * $item['quantity'];
                        $total += $subtotal;

                        $payload[] = [
                            'menu_id' => $menu->id,
                            'quantity' => $item['quantity'],
                            'price' => $menu->price,
                            'subtotal' => $subtotal,
                        ];

                        // Kurangi stok baru
                        Menu::where('id', $menu->id)
                            ->decrement('stock', $item['quantity']);
                    }

                    $order->orderItems()->createMany($payload);

                } else {
                    $total = $order->total_amount;
                }

                $order->update([
                    'customer_name' => $request->customer_name ?? $order->customer_name,
                    'table_number' => $request->table_number ?? $order->table_number,
                    'order_type' => $request->order_type ?? $order->order_type,
                    'note' => $request->note ?? $order->note,
                    'status' => $request->status ?? $order->status,
                    'total_amount' => $total,
                ]);
            });

            $order->refresh();

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order->load('orderItems.menu')
        ], 200);
    }



    public function destroy(string $id)
    {
        $order = Order::with('orderItems.menu')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        try {
            DB::transaction(function() use ($order) {
                foreach ($order->orderItems as $item) {
                    $menu = $item->menu;
                    $menu->stock += $item->quantity; // kembalikan stock
                    $menu->save();
                }

                $order->orderItems()->delete();
                $order->delete();
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ], 200);
    }
}
