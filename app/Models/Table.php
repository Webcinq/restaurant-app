<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'capacity', 'status', 'qr_code'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function currentOrder()
    {
        return $this->hasOne(Order::class)
            ->whereIn('status', ['en_attente', 'en_preparation', 'pret'])
            ->latest();
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'libre');
    }

    public function isAvailable()
    {
        return $this->status === 'libre';
    }
}
