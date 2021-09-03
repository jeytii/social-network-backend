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
     * @return string
     */
    public function getFullBirthDateAttribute(): string
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

    // =============================
    // FORMATTERS
    // =============================

    /**
     * Format original user data for profile view.
     *
     * @param int  $exceptionId
     * @return array
     */
    public function formatProfileInfo(int $exceptionId): array
    {
        $isSelf = $this->id === $exceptionId;
        $data = collect($this)->merge([
            'birth_date' => $this->full_birth_date,
            'is_self' => $isSelf,
        ]);

        if (!$isSelf) {
            $data->put('slug', $this->slug);
        }

        return $data->toArray();
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
