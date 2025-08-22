<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DishController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\HomeController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Routes Menu Public (pour les clients via QR Code)
Route::get('/table/{table}/menu', function ($table) {
    return view('menu.index', compact('table'));
})->name('table.menu');

// Routes Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Categories
    Route::resource('categories', CategoryController::class);
    Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
    
    // Dishes
    Route::resource('dishes', DishController::class);
    Route::patch('dishes/{dish}/toggle-availability', [DishController::class, 'toggleAvailability'])->name('dishes.toggle-availability');
    
    // Tables
    Route::resource('tables', TableController::class);
    Route::patch('tables/{table}/change-status', [TableController::class, 'changeStatus'])->name('tables.change-status');
    Route::get('tables/{table}/qr-code', [TableController::class, 'generateQrCode'])->name('tables.qr-code');
    
    // Orders
    Route::resource('orders', OrderController::class)->except(['create', 'store']);
    Route::patch('orders/{order}/change-status', [OrderController::class, 'changeStatus'])->name('orders.change-status');
    Route::get('orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
});

