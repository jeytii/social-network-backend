<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'gender',
        'birth_month',
        'birth_day',
        'birth_year',
        'location',
        'bio',
        'image_url',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'password',
        'email_verified_at',
        'updated_at',
        'pivot',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'date:F Y',
        'email_verified_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->setAttribute('slug', uniqid());
        });
    }

    // =============================
    // GETTERS
    // =============================

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get the user's full birth date.
     *
     * @return string
     */
    public function getFullBirthDateAttribute()
    {
        return "$this->birth_month $this->birth_day, $this->birth_year";
    }

    // =============================
    // RELATIONSHIPS
    // =============================
    
    /**
     * People/users who follow the auth user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers()
    {
        return $this->belongsToMany('App\Models\User', 'connections', 'following_id', 'follower_id')->withPivot('created_at');
    }

    /**
     * People/users that the auth user follows.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following()
    {
        return $this->belongsToMany('App\Models\User', 'connections', 'follower_id', 'following_id')->withPivot('created_at');
    }
}
