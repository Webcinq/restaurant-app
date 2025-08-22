<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Category;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class DishController extends Controller
{
    public function index()
    {
        $dishes = Dish::with('category')->paginate(15);
        return view('admin.dishes.index', compact('dishes'));
    }

    public function create()
    {
        $categories = Category::active()->ordered()->get();
        return view('admin.dishes.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_available' => 'boolean',
            'preparation_time' => 'integer|min:0'
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = public_path('storage/dishes/' . $filename);
            
            Image::make($image)->resize(400, 300)->save($path);
            $data['image'] = 'dishes/' . $filename;
        }

        Dish::create($data);

        return redirect()->route('admin.dishes.index')
            ->with('success', 'Plat créé avec succès');
    }

    public function show(Dish $dish)
    {
        $dish->load('category', 'orderItems.order');
        return view('admin.dishes.show', compact('dish'));
    }

    public function edit(Dish $dish)
    {
        $categories = Category::active()->ordered()->get();
        return view('admin.dishes.edit', compact('dish', 'categories'));
    }

    public function update(Request $request, Dish $dish)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_available' => 'boolean',
            'preparation_time' => 'integer|min:0'
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($dish->image && file_exists(public_path('storage/' . $dish->image))) {
                unlink(public_path('storage/' . $dish->image));
            }

            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = public_path('storage/dishes/' . $filename);
            
            Image::make($image)->resize(400, 300)->save($path);
            $data['image'] = 'dishes/' . $filename;
        }

        $dish->update($data);

        return redirect()->route('admin.dishes.index')
            ->with('success', 'Plat mis à jour avec succès');
    }

    public function destroy(Dish $dish)
    {
        // Vérifier si le plat est dans des commandes en cours
        $activeOrders = $dish->orderItems()
            ->whereHas('order', function($query) {
                $query->whereIn('status', ['en_attente', 'en_preparation', 'pret']);
            })->count();

        if ($activeOrders > 0) {
            return redirect()->route('admin.dishes.index')
                ->with('error', 'Impossible de supprimer un plat présent dans des commandes actives');
        }

        // Supprimer l'image
        if ($dish->image && file_exists(public_path('storage/' . $dish->image))) {
            unlink(public_path('storage/' . $dish->image));
        }

        $dish->delete();

        return redirect()->route('admin.dishes.index')
            ->with('success', 'Plat supprimé avec succès');
    }

    public function toggleAvailability(Dish $dish)
    {
        $dish->update(['is_available' => !$dish->is_available]);
        
        return response()->json([
            'success' => true,
            'status' => $dish->is_available
        ]);
    }
}