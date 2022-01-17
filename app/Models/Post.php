<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{
    BelongsTo,
    BelongsToMany,
    HasMany,
    MorphToMany
};

class Post extends Model
{
    use HasUuid, HasFactory;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

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
        'pivot',
        'created_at',
        'updated_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'key',
        'is_own_post',
        'is_liked',
        'is_edited',
        'is_bookmarked',
        'timestamp',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'user:id,slug,name,username,gender,image_url'
    ];

    /**
     * The number of relationships that should always be loaded.
     *
     * @var array
     */
    protected $withCount = [
        'likers as likes_count',
        'comments'
    ];

    // =============================
    // OVERRIDE DEFAULTS
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

    // =============================
    // CUSTOM ATTRIBUTES
    // =============================

    /**
     * Generate a unique key.
     *
     * @return string
     */
    public function getKeyAttribute(): string
    {
        return uniqid("{$this->slug}-");
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
     * Check if the post is liked by the auth user.
     *
     * @return bool
     */
    public function getIsLikedAttribute(): bool
    {
        return (bool) $this->likers()->find(auth()->id());
    }

    /**
     * Check if the post's body attribute has been updated.
     *
     * @return bool
     */
    public function getIsEditedAttribute(): bool
    {
        return $this->created_at < $this->updated_at;
    }

    /**
     * Check if the post is bookmarked by the user.
     *
     * @return bool
     */
    public function getIsBookmarkedAttribute(): bool
    {
        return (bool) $this->bookmarkers()->find(auth()->id());
    }

    /**
     * Get the time difference between now and date of creation.
     * 
     * @return string
     */
    public function getTimestampAttribute(): string
    {
        if (now()->diffInSeconds($this->created_at) <= 59) {
            return 'Just now';
        }

        $minutes = now()->diffInMinutes($this->created_at);

        if ($minutes === 1) {
            return 'A minute ago';
        }

        if ($minutes >= 2 && $minutes <= 59) {
            return "{$minutes} minutes ago";
        }

        $hours = now()->diffInHours($this->created_at);

        if ($hours === 1) {
            return '1 hour ago';
        }

        if ($hours <= 23) {
            return "{$hours} hours ago";
        }

        if (now()->diffInDays($this->created_at) === 1) {
            return "Yesterday at {$this->created_at->format('g:i A')}";
        }

        return $this->created_at->format('F d, Y (g:i A)');
    }

    // =============================
    // RELATIONSHIPS
    // =============================

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
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function likers(): MorphToMany
    {
        return $this->morphToMany('App\Models\User', 'likable')->withPivot('created_at');
    }

    /**
     * Get the users who bookmarked the post.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bookmarkers(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User', 'bookmarks', 'bookmark_id', 'user_id')
            ->withPivot('created_at');
    }
}
