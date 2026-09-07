<?php

namespace App\Http\Controllers;

use App\Support\DemoCatalog;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $items = DemoCatalog::cartItems();

        return view('cart.index', [
            'items' => $items,
            'total' => array_sum(array_map(fn ($item) => $item->price, $items)),
        ]);
    }
}
