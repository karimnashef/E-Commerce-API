<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('products', 'user')->latest()->get();
        return response()->json($orders);
    }

    public function myOrders(Request $request)
    {
        $orders = Order::with('products', 'user')
            ->where('user_id', Auth::user()->id)
            ->latest()
            ->get();

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    public function receivedOrders(Request $request)
    {
        $orders = Order::with('products', 'user')
            ->whereHas('products', function ($query) {
                $query->where('user_id', Auth::user()->id);
            })
            ->get();

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.qty' => 'required|integer|min:1'
        ]);

        $user = Auth::user();

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 0
        ]);

        $total = 0;

        foreach ($request->products as $item) {
            $product = Product::findOrFail($item['product_id']);

            $subtotal = $product->price * $item['qty'];

            $order->products()->attach($product->id, ['quantity' => $item['qty'], 'price' => $product->price]);

            $total += $subtotal;
        }

        $order->update([
            'total' => $total
        ]);

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => $order->user_id,
            'title' => 'New Order Created',
            'body' => 'Your order has been created successfully.',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $notificationsSellerId = $order->products->pluck('user_id')->unique();

        foreach ($notificationsSellerId as $sellerId) {
            DB::connection('supabase')->table('notifications')->insert([
                'user_id' => $sellerId,
                'title' => 'New Order Received',
                'body' => 'You have received a new order from ' . Auth::user()->name . '.',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order
        ]);
    }

    public function cancel($id)
    {
        $order = Order::findOrFail($id);

        $order->update(['status' => 'cancelled']);

        $notificationsSellerId = $order->products->pluck('user_id')->unique();

        foreach ($notificationsSellerId as $sellerId) {
            DB::connection('supabase')->table('notifications')->insert([
                'user_id' => $sellerId,
                'title' => 'Order Cancelled',
                'body' => 'An order from ' . Auth::user()->name . ' has been cancelled.',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json(['message' => 'Order cancelled']);
    }

    public function confirm($id)
    {
        $order = Order::findOrFail($id);

        $order->update(['status' => 'confirmed']);

       $notificationsSellerId = $order->products->pluck('user_id')->unique();

        foreach ($notificationsSellerId as $sellerId) {
            DB::connection('supabase')->table('notifications')->insert([
                'user_id' => $sellerId,
                'title' => 'Order Confirmed',
                'body' => 'An order from ' . Auth::user()->name . ' has been confirmed.',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json(['message' => 'Order confirmed']);
    }

    public function reject($id)
    {
        $order = Order::findOrFail($id);

        $productsIds = Product::where('user_id', Auth::user()->id)->pluck('id');

        $orderProducts = $order->products()->whereIn('product_id', $productsIds)->pluck('id');

       $order->products()->updateExistingPivot($orderProducts, ['status' => 'rejected']);

       if($order->products()->wherePivot('status', '!=', 'rejected')->count() == 0) {
            $order->update(['status' => 'rejected']);
        }

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => $order->user_id,
            'title' => 'Order Rejected',
            'body' => 'Your products have been rejected by the seller.',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Products rejected']);
    }

    public function complete($id)
    {
        $order = Order::findOrFail($id);

        $productsIds = Product::where('user_id', Auth::user()->id)->pluck('id');

        $orderProducts = $order->products()->whereIn('product_id', $productsIds)->pluck('id');

        $order->products()->updateExistingPivot($orderProducts, ['status' => 'completed']);

        if($order->products()->wherePivot('status', '!=', 'completed')->count() == 0) {
            $order->update(['status' => 'completed']);
        }

        DB::connection('supabase')->table('notifications')->insert([
            'user_id' => $order->user_id,
            'title' => 'Order Completed',
            'body' => 'Your products have been completed.',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Products completed']);
    }
}
