<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            return response()->json([
                "success" => true,
                "message" => "Resources data not found",
            ], 200);
        }

        return response()->json([
            "success" => true,
            "message" => "Get all resources",
            "data" => $categories
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
        ]);
        //2. check validaror error
        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => "Validation Error.",
                "errors" => $validator->errors()
            ], 422);
        }

        //4. insert data
        $categories = Category::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        //5. response
        return response()->json([
            "success" => true,
            "message" => "Category created successfully",
            "data" => $categories
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                "success" => false,
                "message" => "Resource not found",
            ], 404);
        }

        return response()->json([
            "success" => true,
            "message" => "Get detail resource",
            "data" => $category
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // 1. mencari data
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                "success" => false,
                "message" => "Resource not found",
            ], 404);
        }

        //2. validator
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => "Validation Error.",
                "errors" => $validator->errors()
            ], 422);
        }

        //3. siapkan data yang ingin di update
        $data = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        //5. update data ke database
        $category->update($data);

        return response()->json([
            "success" => true,
            "message" => "Resource updated successfully",
            "data" => $category
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                "success" => false,
                "message" => "Resource not found",
            ], 404);
        }

        $category->delete();

        return response()->json([
            "success" => true,
            "message" => "Resource deleted successfully",
        ]);
    }
}
