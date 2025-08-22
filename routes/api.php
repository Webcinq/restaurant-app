<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\TableController as ApiTableController;

Route::prefix('v1')->group(function () {
    // Menu endpoints
    Route::get('/menu', [MenuController::class, 'index']);
    Route::get('/menu/categories', [MenuController::class, 'categories']);
    Route::get('/menu/category/{category}', [MenuController::class, 'dishsByCategory']);
    Route::get('/dishes/{dish}', [MenuController::class, 'dishDetails']);
    
    // Table endpoints
    Route::get('/tables/{table}', [ApiTableController::class, 'show']);
    Route::get('/tables/{table}/menu', [ApiTableController::class, 'menu']);
    Route::get('/tables/{table}/current-order', [ApiTableController::class, 'currentOrder']);
    
    // Order endpoints
    Route::post('/orders', [ApiOrderController::class, 'store']);
    Route::get('/orders/{order}', [ApiOrderController::class, 'show']);
    Route::patch('/orders/{order}/add-item', [ApiOrderController::class, 'addItem']);
    Route::patch('/orders/{order}/update-item/{orderItem}', [ApiOrderController::class, 'updateItem']);
    Route::delete('/orders/{order}/remove-item/{orderItem}', [ApiOrderController::class, 'removeItem']);
    Route::patch('/orders/{order}/update-notes', [ApiOrderController::class, 'updateNotes']);
});

// Middleware pour vérifier les rôles admin
// app/Http/Middleware/AdminMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé');
        }

        return $next($request);
    }
}
