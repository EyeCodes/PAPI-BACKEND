<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Roles extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
    ];

    public static function defaultRoles()
    {
        return [
            'admin',
            'merchant',
            'customer'
        ];
    }

    protected static function booted()
    {
        static::creating(function ($role) {
            $role->guard_name = $role->guard_name ?? 'web';
        });
    }
}