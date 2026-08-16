<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function returnTransaction()
    {
        return $this->belongsTo(ReturnTransaction::class, 'return_transaction_id');
    }

    public function productBranch()
    {
        return $this->belongsTo(ProductBranch::class, 'product_branch_id');
    }
}
