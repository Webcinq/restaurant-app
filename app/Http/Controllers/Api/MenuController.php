<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dish;

class MenuController extends Controller
{
    public function index()
    {
        $categories = Category::active()
            ->ordered()
            ->with(['activeDishes' => function($query) {
                $query->orderBy('name');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function categories()
    {
        $categories = Category::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function dishsByCategory(Category $category)
    {
        $dishes = $category->activeDishes()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'category' => $category,
            'dishes' => $dishes
        ]);
    }

    public function dishDetails(Dish $dish)
    {
        if (!$dish->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Ce plat n\'est pas disponible'
            ], 404);
        }

        $dish->load('category');

        return response()->json([
            'success' => true,
            'data' => $dish
        ]);
    }
}
