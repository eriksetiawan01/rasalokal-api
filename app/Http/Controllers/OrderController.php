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
                    'customer_phone' => $request->customer_phone,
                    'table_number'   => $request->table_number,
                    'order_type'     => $request->order_type,
                    'total_amount'   => $total,
                    'status'         => 'pending',
                    'payment_status' => 'unpaid',
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
        $order = Order::with('orderItems.menu', 'payment')->find($id);

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

        $validator = Validator::make($request->all(), [
            'status' => 'in:pending,processing,completed,cancelled',
            'payment_status' => 'in:paid,unpaid',
            'items' => 'array|min:1',
            'items.*.menu_id' => 'exists:menus,id',
            'items.*.quantity' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'data' => $validator->errors()
            ], 422);
        }

        try {
            DB::transaction(function() use ($request, $order) {
                $total = 0;

                if ($request->has('items')) {
                    // kembalikan stock lama
                    foreach ($order->orderItems as $oldItem) {
                        $oldMenu = $oldItem->menu;
                        $oldMenu->stock += $oldItem->quantity;
                        $oldMenu->save();
                    }

                    $order->orderItems()->delete();

                    $menuIds = collect($request->items)->pluck('menu_id');
                    $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');

                    $orderItemsPayload = [];

                    foreach ($request->items as $item) {
                        $menu = $menus[$item['menu_id']];
                        if ($menu->stock < $item['quantity']) {
                            throw new \Exception("Stok menu '{$menu->name}' tidak mencukupi.");
                        }

                        $subtotal = $menu->price * $item['quantity'];
                        $total += $subtotal;

                        $orderItemsPayload[] = [
                            'menu_id' => $menu->id,
                            'quantity' => $item['quantity'],
                            'price' => $menu->price,
                            'subtotal' => $subtotal,
                        ];

                        $menu->stock -= $item['quantity'];
                        $menu->save();
                    }

                    $order->orderItems()->createMany($orderItemsPayload);
                } else {
                    $total = $order->total_amount;
                }

                $order->update(array_merge(
                    $request->only(['status', 'payment_status']),
                    ['total_amount' => $total]
                ));
            });
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

        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Paid orders cannot be deleted.'
            ], 400);
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
