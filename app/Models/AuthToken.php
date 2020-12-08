<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// I considered using Sanctum for tokens but I don't really want users with tokens, just tokens
// adapted from https://github.com/laravel/sanctum/blob/5c5a4cb9dc87c151802103e769a407bcf90f7cfa/src/PersonalAccessToken.php
class AuthToken extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'token',
    ];


    public static function boot()
    {
        parent::boot();
        static::creating(function ($token) {
            $token->token = Str::random(64);
        });
    }
}
