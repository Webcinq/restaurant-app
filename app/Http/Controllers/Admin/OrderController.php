<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['table', 'user', 'orderItems.dish']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('date') && $request->date !== '') {
            $query->whereDate('created_at', $request->date);
        }

        $orders = $query->latest()->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['table', 'user', 'orderItems.dish']);
        return view('admin.orders.show', compact('order'));
    }

    public function changeStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:en_attente,en_preparation,pret,servi,annule'
        ]);

        $order->update(['status' => $request->status]);

        // Mettre à jour le statut de la table si nécessaire
        if ($request->status === 'servi') {
            $order->table->update(['status' => 'libre']);
        } elseif ($request->status === 'en_attente' && $order->table->status === 'libre') {
            $order->table->update(['status' => 'occupee']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut de la commande mis à jour'
        ]);
    }

    public function print(Order $order)
    {
        $order->load(['table', 'orderItems.dish']);
        return view('admin.orders.print', compact('order'));
    }
}
