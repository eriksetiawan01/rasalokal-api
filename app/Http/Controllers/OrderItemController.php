<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class OrderItemController extends Controller
{
    public function index()
    {
        $orderItems = OrderItem::with(['order', 'menu'])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List of order items',
            'data' => $orderItems
        ]);
    }

    public function store(Request $request)
    {

    $order = Order::findOrFail($request->order_id);
    $menu = Menu::findOrFail($request->menu_id);

    $request->validate([
        'order_id' => 'required|exists:orders,id',
        'menu_id'  => 'required|exists:menus,id',
        'quantity' => 'required|integer|min:1',
        'price'    => 'required|exists:menus,id',
    ]);

    // Hitung subtotal
    $subtotal = $request->quantity * $request->price;

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'menu_id'  => $menu->id,
        'quantity' => $request->quantity,
        'price'    => $menu->price,
        'subtotal' => $subtotal,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Order item created successfully',
        'data'    => $orderItem
    ]);
    }

    public function show(string $id)
    {
        $orderItems = OrderItem::with('order')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'OrderItem detail fetched',
            'data' => $orderItems
        ]);
    }

    // public function update(Request $request, string $id)
    // {
    // $orderItem = OrderItem::findOrFail($id);

    // $validator = Validator::make(request()->all(), [
    //     'order_id' => 'exists:orders,id',
    //     'menu_id'  => 'exists:menus,id',
    //     'quantity' => 'required|integer|min:1',
    // ]);
    // //2. check validaror error
    // if ($validator->fails()) {
    //     return response()->json([
    //         "success" => false,
    //         "message" => "Validation Error.",
    //         "errors" => $validator->errors()
    //     ], 422);
    // }

    // // Update value jika ada
    // $data = [
    //         'order_id' => $request->order_id,
    //         'menu_id'  => $request->menu_id,
    //         'quantity' => $request->quantity,
    //     ];

    // // Hitung ulang subtotal
    // $quantity = $request->quantity ?? $orderItem->quantity;
    // $price = $orderItem->price;
    // $subtotal = $quantity * $price;

    // $orderItem->update($data + [
    //     'quantity' => $quantity,
    //     'price'    => $price,
    //     'subtotal' => $subtotal,
    // ]);

    // return response()->json([
    //     'success' => true,
    //     'message' => 'Order item updated successfully',
    //     'data'    => $orderItem
    // ]);
    // }

    public function update(Request $request, string $id)
    {
        // Validasi
        $item = OrderItem::find($id);

        if (!$item) {
            return response()->json([
                "success" => false,
                "message" => "Resource not found",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'menu_id' => 'required|exists:menus,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric',
            'subtotal' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $menu = Menu::find($request->menu_id);

        $price = $menu->price;
        $subtotal = $price * $request->quantity;

        $data = [
            'order_id' => $request->order_id,
            'menu_id' => $request->menu_id,
            'quantity' => $request->quantity,
            'price' => $price,
            'subtotal' => $subtotal,
        ];

        $item->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Order item berhasil diupdate',
            'data' => $item
        ], 200);
    }

    public function destroy(string $id)
    {
        $orderItems = OrderItem::findOrFail($id);
        $orderItems->delete();

        return response()->json([
            'success' => true,
            'message' => 'OrderItem deleted successfully'
        ]);
    }
}
