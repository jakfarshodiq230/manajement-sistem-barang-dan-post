<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'address', 'parent_id', 'status', 'logo', 'qris_image'];

    public function parent()
    {
        return $this->belongsTo(Owner::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Owner::class, 'parent_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
