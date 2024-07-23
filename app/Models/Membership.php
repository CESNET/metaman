<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Membership extends Pivot
{
    protected $table = 'memberships';

    protected $casts = [
        'approved' => 'boolean',
    ];

    public function entity()
    {
        return $this->belongsTo('App\Models\Entity');
    }

    public function federation()
    {
        return $this->belongsTo('App\Models\Federation');
    }

    public function requester()
    {
        return $this->belongsTo('App\Models\User', 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo('App\Models\User', 'approved_by');
    }
}
