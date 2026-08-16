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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productBranches()
    {
        return $this->hasMany(ProductBranch::class);
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }


}