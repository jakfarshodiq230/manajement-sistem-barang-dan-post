<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_branch_id',
        'qty',
        'cost_price',
        'entry_date',
        'expiration_date',
    ];

    public function productBranch()
    {
        return $this->belongsTo(ProductBranch::class);
    }
}
