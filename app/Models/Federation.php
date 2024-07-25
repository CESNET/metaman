<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Federation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'tagfile',
        'cfgfile',
        'xml_id',
        'xml_name',
        'filters',
        'additional_filters',
        'explanation',
    ];

    protected $casts = [
        'approved' => 'boolean',
    ];

    public function operators()
    {
        return $this
            ->belongsToMany('App\Models\User')
            ->orderBy('name')
            ->withTimestamps();
    }

    public function entities()
    {
        return $this->belongsToMany('App\Models\Entity', 'memberships')
            ->using('App\Models\Membership')
            ->withPivot('approved')
            ->wherePivot('approved', true)
            ->withTimestamps();
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->admin) {
            return $query->withTrashed();
        }

        // FIXME: add federations if the user is its operator!
    }

    public function scopeSearch($query, ?string $search = null)
    {
        $query
            ->where('name', 'like', "%$search%")
            ->orWhere('description', 'like', "%$search%")
            ->orWhere('xml_id', 'like', "%$search%")
            ->orWhere('xml_name', 'like', "%$search%");
    }
}
