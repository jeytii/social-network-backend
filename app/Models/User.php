<?php

namespace App\Models;

use App\Models\Notification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsToMany, MorphToMany, MorphMany};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\{Str, Carbon};
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

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
    protected $fillable = [
        'name',
        'email',
        'username',
        'gender',
        'birth_date',
        'location',
        'bio',
        'image_url',
        'dark_mode',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'birth_date',
        'color',
        'dark_mode',
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
        'birth_date' => 'date:F d, Y',
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
            $user->setAttribute('id', (string) Str::uuid());
            $user->setAttribute('slug', uniqid());
            $user->setAttribute('birth_date', Carbon::parse($user->birth_date));
        });
    }

    /**
     * Check if the user has reached the rate limit for changing the password.
     * 
     * @return bool
     */
    public function passwordResetLimitReached(): bool
    {
        $maxAttempts = config('validation.attempts.change_password.max');
        $query = DB::table('password_resets')->where('email', $this->email)->whereNotNull('completed_at');

        if ($query->count() >= $maxAttempts) {
            $resets = $query->orderByDesc('completed_at')->limit($maxAttempts)->get();
            $lastTimestamp = Carbon::parse($resets->last()->completed_at);
            $diffInHours = $lastTimestamp->diffInHours(now());

            return $diffInHours <= config('validation.attempts.change_password.interval');
        }

        return false;
    }

    /**
     * Check if the user has reached the rate limit for updating some settings.
     * 
     * @param string  $column
     * @param int  $maxAttempts
     * @param int  $interval
     * @return bool
     */
    public function settingsUpdateLimitReached(string $column, int $maxAttempts, int $interval): bool
    {
        $query = DB::table('settings_updates')->where('user_id', auth()->id())->where('type', $column);

        if ($query->count() >= $maxAttempts) {
            $resets = $query->latest()->limit($maxAttempts)->get();
            $lastTimestamp = Carbon::parse($resets->last()->completed_at);
            $diffInHours = $lastTimestamp->diffInHours(now());

            return $diffInHours <= $interval;
        }

        return false;
    }

    // =============================
    // SCOPES
    // =============================

    /**
     * Scope a query to find a user by username or email address.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $username
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUsername(Builder $query, string $username)
    {
        return $query->where('username', $username)->orWhere('email', $username);
    }

    /**
     * Scope a query to get a user with only the basic data.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFirstWithBasicOnly(Builder $query)
    {
        return $query->first(array_merge(config('response.user'), ['id', 'email']))
            ->setHidden(['id', 'is_followed', 'is_self']);
    }

    // =============================
    // OVERRIDE DEFAULTS
    // =============================

    /**
     * The channels the user receives notification broadcasts on.
     *
     * @return string
     */
    public function receivesBroadcastNotificationsOn()
    {
        return 'notify.user.' . $this->slug;
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

    // =============================
    // CUSTOM ATTRIBUTES
    // =============================

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
        if ($this->is_self) {
            return null;
        }

        return $this->followers()->whereKey(auth()->id())->exists();
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
        return $this->belongsToMany('App\Models\User', 'connections', 'following_id', 'follower_id')
                    ->withPivot('created_at');
    }

    /**
     * Get the followed people of a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User', 'connections', 'follower_id', 'following_id')
                    ->withPivot('created_at');
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
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function likedPosts(): MorphToMany
    {
        return $this->morphedByMany('App\Models\Post', 'likable')->withPivot('created_at');
    }

    /**
     * Get the comments liked by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function likedComments(): MorphToMany
    {
        return $this->morphedByMany('App\Models\Comment', 'likable')->withPivot('created_at');
    }

    /**
     * Get the posts bookmarked by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Post', 'bookmarks', 'user_id', 'bookmark_id')
                    ->withPivot('created_at');
    }

    /**
     * Get the entity's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')->latest();
    }
}
