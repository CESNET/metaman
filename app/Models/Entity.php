<?php

namespace App\Models;

use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'entityid',
        'file',
        'xml_file',
        'name_en',
        'name_cs',
        'description_en',
        'description_cs',
        'edugain',
        'hfd',
        'rs',
        'cocov1',
        'sirtfi',
        'metadata',
    ];

    protected $casts = [
        'type' => EntityType::class,
        'edugain' => 'boolean',
        'hfd' => 'boolean',
        'rs' => 'boolean',
        'cocov1' => 'boolean',
        'sirtfi' => 'boolean',
        'approved' => 'boolean',
    ];

    public function operators()
    {
        return $this
            ->belongsToMany('App\Models\User')
            ->orderBy('name')
            ->withTimestamps();
    }

    public function federations()
    {
        return $this->belongsToMany('App\Models\Federation', 'memberships')
            ->using('App\Models\Membership')
            ->withPivot('approved')
            ->wherePivot('approved', true)
            ->withTimestamps();
    }

    public function federationsRequested()
    {
        return $this->belongsToMany('App\Models\Federation', 'memberships')
            ->using('App\Models\Membership')
            ->withPivot('approved')
            ->wherePivot('approved', false)
            ->withTimestamps();
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Models\Group');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->admin) {
            return $query->withTrashed();
        }
    }

    public function scopeSearch($query, string $search = null)
    {
        $query
            ->where('entityid', 'like', "%$search%")
            ->orwhere('name_en', 'like', "%$search%")
            ->orWhere('name_cs', 'like', "%$search%")
            ->orWhere('description_en', 'like', "%$search%")
            ->orWhere('description_cs', 'like', "%$search%");
    }
}
