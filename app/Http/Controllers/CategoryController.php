<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Container\Attributes\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CategoryController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }
    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'user_id' => Auth::user()->id,
        ]);

        FacadesDB::connection('supabase')->table('notifications')->insert([
            'user_id' => Auth::user()->id,
            'title'   => 'Category Created',
            'body' => "Category '{$category->name}' has been created successfully.",
        ]);

        return response()->json([
            'success' => true,
            'category' => $category
        ], 201);
    }
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $this->authorize('update', $category);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        FacadesDB::connection('supabase')->table('notifications')->insert([
            'user_id' => Auth::user()->id,
            'title'   => 'Category Updated',
            'body' => "Category '{$category->name}' has been updated successfully.",
        ]);

        return response()->json([
            'success' => true,
            'category' => $category
        ]);
    }
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $this->authorize('delete', $category);

        $category->delete();

        FacadesDB::connection('supabase')->table('notifications')->insert([
            'user_id' => Auth::user()->id,
            'title'   => 'Category Deleted',
            'body' => "Category '{$category->name}' has been deleted successfully.",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.'
        ]);
    }
}
