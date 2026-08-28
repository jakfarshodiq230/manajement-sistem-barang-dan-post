<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_branch_id',
        'batch_number',
        'scc_code',
        'qty',
        'cost_price',
        'price',
        'min_nego_price',
        'entry_date',
        'expiration_date',
    ];

    public function productBranch()
    {
        return $this->belongsTo(ProductBranch::class);
    }
}
