<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class CartController extends Controller
{
    public function saveCart(Request $request)
    {
        $cartData = $request->cart;
        $user = $request->user();

        Redis::setex("cart:".$user->id , 86400 , json_encode($cartData));

        return response()->json(['message' => 'Cart saved successfully']);
    }
    public function getCart()
    {
        $user = request()->user();
        $cartData = Redis::get("cart:".$user->id);

        if ($cartData) {
            return response()->json(['cart' => json_decode($cartData)]);
        } else {
            return response()->json(['message' => 'No cart found'], 404);
        }
    }
    public function removeFromCart(Request $request)
    {
        $user = $request->user();
        Redis::del("cart:".$user->id);

        return response()->json(['message' => 'Cart removed successfully']);
    }
}
