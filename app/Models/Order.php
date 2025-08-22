<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id', 'user_id', 'order_number', 
        'status', 'total_amount', 'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function calculateTotal()
    {
        $this->total_amount = $this->orderItems->sum('total_price');
        $this->save();
        return $this->total_amount;
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total_amount, 2) . ' DH';
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            $order->order_number = 'CMD-' . date('Ymd') . '-' . str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT);
        });
    }
}
