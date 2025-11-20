<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'supplier_id',
        'quantity',
        'order_date',
        'expected_date',
        'status',
        'order_value',
        'received',
        'received_date',
        'user_id',
        'store_id',
    ];

    // ✅ Champs virtuels
    protected $appends = ['is_late', 'status_label', 'status_color'];

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // ✅ Getter pour is_late
    public function getIsLateAttribute()
    {
        return !$this->received
            && $this->expected_date
            && $this->expected_date < now()->toDateString();
    }

    // ✅ Getter pour status_label
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'Confirmed' => 'En cours',
            'Out for delivery' => 'En livraison',
            'Delayed' => 'Retardée',
            'Returned' => 'Retournée',
            'Delivered' => 'Livrée',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'Confirmed' => 'blue',       // En cours
            'Out for delivery' => 'orange', // En livraison
            'Delayed' => 'red',          // Retardée
            'Returned' => 'gray',        // Retournée
            'Delivered' => 'green',      // Livrée
            default => 'black',
        };
    }
}
