<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProductController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $products = Product::with(['user', 'category'])->where('user_id', Auth::user()->id)->get();

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }
    public function showAll()
    {
        $products = Product::with(['user', 'category'])->where('stock', '>', 0)->where('user_id', '!=', Auth::user()->id)->get();

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }
    public function show($id)
    {
        $product = Product::with(['user', 'category'])->findOrFail($id)->where('stock', '>', 0)->get();

        return response()->json([
            'success' => true,
            'product' => $product
        ]);
    }
    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'color' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'user_id' => Auth::user()->id,
            'category_id' => $request->category_id,
            'price' => $request->price,
            'stock' => $request->stock,
            'color' => $request->color,
            'description' => $request->description,
        ]);

        $product->load('category');

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => Auth::user()->id,
            'title' => 'New Product Added',
            'body' => $product->name . ' has been added.'
        ]);

        return response()->json([
            'success' => true,
            'product' => $product
        ], 201);
    }
    public function update(Request $request, $id)
    {
        $product = Product::where('user_id', Auth::user()->id)->findOrFail($id);
        $this->authorize('update', $product);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'price' => 'sometimes|required|numeric',
            'stock' => 'sometimes|required|integer',
            'color' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $product->update($request->only([
            'name', 'category_id', 'price', 'stock', 'color', 'description'
        ]));

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => Auth::user()->id,
            'title' => 'Product Updated',
            'body' => $product->name . ' has been updated.'
        ]);

        return response()->json([
            'success' => true,
            'product' => $product
        ]);
    }
    public function destroy($id)
    {
        $product = Product::where('user_id', Auth::user()->id)->findOrFail($id);
        $this->authorize('delete', $product);
        $product->delete();

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => Auth::user()->id,
            'title' => 'Product Deleted (' . $product->id . ')',
            'body' => $product->name . ' has been deleted.'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.'
        ]);
    }
}
