<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['body'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'user_id',
        'created_at',
        'updated_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_own_post',
        'is_edited',
        'timestamp',
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
     * Check if the post is owned by the auth user.
     *
     * @return bool
     */
    public function getIsOwnPostAttribute(): bool
    {
        return $this->user_id === auth()->id();
    }

    /**
     * Check if the post's body attribute has been updated.
     *
     * @return bool
     */
    public function getIsEditedAttribute(): bool
    {
        return $this->isDirty('body');
    }

    /**
     * Get the time difference between now and date of creation.
     * 
     * @return string
     */
    public function getTimestampAttribute(): string
    {
        if (now()->diffInSeconds($this->created_at) <= 59) {
            return 'A few seconds ago';
        }

        $minutes = now()->diffInMinutes($this->created_at);

        if ($minutes === 1) {
            return 'A minute ago';
        }

        if ($minutes >= 2 && $minutes <= 59) {
            return "{$minutes} minutes ago";
        }

        $hours = now()->diffInHours($this->created_at);

        if ($hours >= 1 && $hours <= 23) {
            return "{$hours} hours ago";
        }

        if (now()->diffInDays($this->created_at) === 1) {
            return "Yesterday at {$this->created_at->format('g:i A')}";
        }

        return $this->created_at->format('F d, Y (g:i A)');
    }

    /**
     * Get the user that owns the post.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * Get the comments under the current post.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany('App\Models\Comment');
    }

    /**
     * Get the users who liked the post.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likers(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User', 'likes', 'post_id', 'user_id')->withPivot('created_at');
    }
}
