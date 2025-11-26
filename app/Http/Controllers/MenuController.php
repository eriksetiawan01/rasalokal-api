<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $menus = Menu::all();

        if ($menus->isEmpty()) {
            return response()->json([
                "success" => true,
                "message" => "Resources data not found",
            ], 200);
        }

        return response()->json([
            "success" => true,
            "message" => "Get all resources",
            "data" => $menus
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //1. validator
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'required|exists:categories,id',
        ]);
        //2. check validaror error
        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => "Validation Error.",
                "errors" => $validator->errors()
            ], 422);
        }

        //3. upload image
        $image = $request->file('photo');
        $image->store('menus','public');

        //4. insert data
        $menu = Menu::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'photo' => $image->hashName(),
            'category_id' => $request->category_id,
        ]);

        //5. response
        return response()->json([
            "success" => true,
            "message" => "Menu created successfully",
            "data" => $menu
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                "success" => false,
                "message" => "Resource not found",
            ], 404);
        }

        return response()->json([
            "success" => true,
            "message" => "Get detail resource",
            "data" => $menu
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // 1. mencari data
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                "success" => false,
                "message" => "Resource not found",
            ], 404);
        }

        //2. validator
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors()
            ], 422);
        }

        //3. siapkan data yang ingin di update
        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'photo' => $request->photo,
            'category_id' => $request->category_id,
        ];

        //4. handle image (upload & delete old image)
        if ($request->hasFile('photo')) {
            //delete old image
            if ($menu->photo) {
                Storage::disk('public')->delete('menus/'.$menu->photo);
            }
            //upload new image
            $image = $request->file('photo');
            $image->store('menus','public');
            $data['photo'] = $image->hashName();
        }

        //5. update data ke database
        $menu->update($data);

        return response()->json([
            "success" => true,
            "message" => "Resource updated successfully",
            "data" => $menu
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                "success" => false,
                "message" => "Resource not found",
            ], 404);
        }

        if ($menu->photo) {
            //delete from storage
            Storage::disk('public')->delete('menus/'.$menu->photo);
        }

        $menu->delete();

        return response()->json([
            "success" => true,
            "message" => "Resource deleted successfully",
        ]);
    }
}
