<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'uniqueid',
        'email',
        'emails',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        //
    ];

    protected function casts(): array
    {
        return [
            'admin' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function federations()
    {
        return $this
            ->belongsToMany('App\Models\Federation')
            ->withTimestamps();
    }

    public function entities()
    {
        return $this
            ->belongsToMany('App\Models\Entity')
            ->withTimestamps();
    }

    public function scopeActiveAdmins($query)
    {
        return $query
            ->where('admin', true)
            ->where('active', true);
    }

    public function scopeSearch($query, ?string $search = null)
    {
        $query
            ->where('name', 'like', "%$search%")
            ->orWhere('uniqueid', 'like', "%$search%")
            ->orWhere('email', 'like', "%$search$");
    }
}
