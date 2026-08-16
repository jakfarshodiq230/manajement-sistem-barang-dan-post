<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'slug', 'icon', 'sequence', 'parent_id', 'category', 'status', 'description'];

    public function parent()
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(Module::class, 'parent_id');
    }
}
