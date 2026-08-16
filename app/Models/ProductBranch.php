<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBranch extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity, \App\Traits\ScopedByBranch;

    protected $fillable = [
        'product_id',
        'branch_id',
        'cost_price',
        'price',
        'tax_percentage',
        'other_fees',
        'min_nego_price',
        'stock',
    ];

    protected $appends = ['active_batch'];

    public function getActiveBatchAttribute()
    {
        $stockMethod = $this->product->stock_method ?? 'fifo';
        
        $query = $this->productBatches()->where('qty', '>', 0);
        
        if ($stockMethod === 'fefo') {
            $query->orderByRaw('expiration_date IS NULL, expiration_date ASC, entry_date ASC');
        } elseif ($stockMethod === 'lifo') {
            $query->orderBy('entry_date', 'desc')->orderBy('id', 'desc');
        } else {
            $query->orderBy('entry_date', 'asc')->orderBy('id', 'asc');
        }
        
        return $query->first();
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function productBatches()
    {
        return $this->hasMany(ProductBatch::class);
    }
}
