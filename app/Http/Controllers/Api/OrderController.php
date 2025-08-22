<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\Dish;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|exists:dishes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_instructions' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $table = Table::findOrFail($request->table_id);

        // Vérifier s'il y a déjà une commande active pour cette table
        $existingOrder = $table->currentOrder;
        
        if ($existingOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Une commande est déjà en cours pour cette table',
                'order_id' => $existingOrder->id
            ], 422);
        }

        // Créer la commande
        $order = Order::create([
            'table_id' => $request->table_id,
            'notes' => $request->notes
        ]);

        // Ajouter les items
        foreach ($request->items as $item) {
            $dish = Dish::findOrFail($item['dish_id']);
            
            if (!$dish->is_available) {
                continue; // Ignorer les plats non disponibles
            }

            OrderItem::create([
                'order_id' => $order->id,
                'dish_id' => $dish->id,
                'quantity' => $item['quantity'],
                'unit_price' => $dish->price,
                'special_instructions' => $item['special_instructions'] ?? null
            ]);
        }

        // Calculer le total
        $order->calculateTotal();

        // Mettre à jour le statut de la table
        $table->update(['status' => 'occupee']);

        $order->load(['orderItems.dish', 'table']);

        return response()->json([
            'success' => true,
            'message' => 'Commande créée avec succès',
            'data' => $order
        ], 201);
    }

    public function show(Order $order)
    {
        $order->load(['table', 'orderItems.dish']);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    public function addItem(Request $request, Order $order)
    {
        $request->validate([
            'dish_id' => 'required|exists:dishes,id',
            'quantity' => 'required|integer|min:1',
            'special_instructions' => 'nullable|string'
        ]);

        if (!in_array($order->status, ['en_attente', 'en_preparation'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier cette commande'
            ], 422);
        }

        $dish = Dish::findOrFail($request->dish_id);

        if (!$dish->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Ce plat n\'est pas disponible'
            ], 422);
        }

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'dish_id' => $dish->id,
            'quantity' => $request->quantity,
            'unit_price' => $dish->price,
            'special_instructions' => $request->special_instructions
        ]);

        $order->calculateTotal();
        $order->load(['orderItems.dish']);

        return response()->json([
            'success' => true,
            'message' => 'Plat ajouté à la commande',
            'data' => $order
        ]);
    }

    public function updateItem(Request $request, Order $order, OrderItem $orderItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'special_instructions' => 'nullable|string'
        ]);

        if ($orderItem->order_id !== $order->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item non trouvé dans cette commande'
            ], 404);
        }

        if (!in_array($order->status, ['en_attente', 'en_preparation'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier cette commande'
            ], 422);
        }

        $orderItem->update([
            'quantity' => $request->quantity,
            'special_instructions' => $request->special_instructions
        ]);

        $order->calculateTotal();
        $order->load(['orderItems.dish']);

        return response()->json([
            'success' => true,
            'message' => 'Item mis à jour',
            'data' => $order
        ]);
    }

    public function removeItem(Order $order, OrderItem $orderItem)
    {
        if ($orderItem->order_id !== $order->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item non trouvé dans cette commande'
            ], 404);
        }

        if (!in_array($order->status, ['en_attente', 'en_preparation'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier cette commande'
            ], 422);
        }

        $orderItem->delete();
        $order->calculateTotal();
        $order->load(['orderItems.dish']);

        return response()->json([
            'success' => true,
            'message' => 'Item supprimé de la commande',
            'data' => $order
        ]);
    }

    public function updateNotes(Request $request, Order $order)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        $order->update(['notes' => $request->notes]);

        return response()->json([
            'success' => true,
            'message' => 'Notes mises à jour'
        ]);
    }
}