<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'category_id',
        'name',
        'image',
        'sku',
        'description',
        'status',
        'stock_method',
        'brand',
        'barcode',
        'unit',
        'weight',
        'length',
        'width',
        'height',
        'is_returnable',
        'tax_type',
    ];

    protected $appends = [
        'price',
        'cost_price',
        'min_nego_price',
        'stock',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productBranches()
    {
        return $this->hasMany(ProductBranch::class);
    }

    public function getPriceAttribute()
    {
        $user = auth()->user();
        if ($user && !empty($user->branch_id)) {
            $pb = $this->productBranches->where('branch_id', $user->branch_id)->first();
            if ($pb) return (float) $pb->price;
        }
        $pb = $this->productBranches->first();
        return $pb ? (float) $pb->price : 0;
    }

    public function getCostPriceAttribute()
    {
        $user = auth()->user();
        if ($user && !empty($user->branch_id)) {
            $pb = $this->productBranches->where('branch_id', $user->branch_id)->first();
            if ($pb) return (float) $pb->cost_price;
        }
        $pb = $this->productBranches->first();
        return $pb ? (float) $pb->cost_price : 0;
    }

    public function getMinNegoPriceAttribute()
    {
        $user = auth()->user();
        if ($user && !empty($user->branch_id)) {
            $pb = $this->productBranches->where('branch_id', $user->branch_id)->first();
            if ($pb) return (float) $pb->min_nego_price;
        }
        $pb = $this->productBranches->first();
        return $pb ? (float) $pb->min_nego_price : 0;
    }

    public function getStockAttribute()
    {
        $user = auth()->user();
        if ($user && !empty($user->branch_id)) {
            $pb = $this->productBranches->where('branch_id', $user->branch_id)->first();
            if ($pb) return (int) $pb->stock;
        }
        return (int) $this->productBranches->sum('stock');
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }


}