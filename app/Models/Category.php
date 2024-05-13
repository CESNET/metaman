<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'tagfile',
        'xml_value'
    ];

    public function entities()
    {
        return $this->hasMany('App\Models\Entity');
    }

    public function scopeSearch($query, string $search = null)
    {
        $query
            ->where('name', 'like', "%$search%")
            ->orWhere('description', 'like', "%$search%")
            ->orWhere('tagfile', 'like', "%$search%");
    }
}
