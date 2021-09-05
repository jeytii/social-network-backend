<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsToMany};
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_self',
        'is_followed',
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
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the user's full birth date.
     *
     * @return string|null
     */
    public function getFullBirthDateAttribute(): string|null
    {
        if (
            is_null($this->birth_month) &&
            is_null($this->birth_day) &&
            is_null($this->birth_year)
        ) {
            return null;
        }

        return "$this->birth_month $this->birth_day, $this->birth_year";
    }

    /**
     * Check if the user is the authenticated one.
     *
     * @return bool
     */
    public function getIsSelfAttribute(): bool
    {
        return auth()->check() && $this->id === auth()->id();
    }

    /**
     * Check if the user followed by the authenticated user.
     *
     * @return bool|null
     */
    public function getIsFollowedAttribute(): bool|null
    {
        return $this->is_self ?
                null :
                (bool) $this->following()->find($this->id);
    }

    // =============================
    // RELATIONSHIPS
    // =============================
    
    /**
     * Get the followers of a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User', 'connections', 'following_id', 'follower_id')->withPivot('created_at');
    }

    /**
     * Get the followed people of a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User', 'connections', 'follower_id', 'following_id')->withPivot('created_at');
    }

    /**
     * Get the posts for a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany('App\Models\Post');
    }

    /**
     * Get the comments for a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany('App\Models\Comment');
    }

    /**
     * Get the posts liked by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Post', 'likes', 'user_id', 'post_id')->withPivot('created_at');
    }

    /**
     * Get the posts bookmarked by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Post', 'bookmarks', 'user_id', 'bookmark_id')->withPivot('created_at');
    }
}
