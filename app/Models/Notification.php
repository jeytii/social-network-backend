<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    use HasUuid;

    const FOLLOWED = 1;
    const LIKED_POST = 2;
    const LIKED_COMMENT = 3;
    const COMMENTED_ON_POST = 4;
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'peeked_at',
        'read_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'user',
        'message',
        'url',
        'is_read',
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
     * Get the message.
     *
     * @return string|null
     */
    public function getMessageAttribute(): string|null
    {
        $data = $this->data;

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if ($data['action'] === self::FOLLOWED) {
            return "followed you.";
        }
        
        if ($data['action'] === self::LIKED_POST) {
            return "liked your post.";
        }

        if ($data['action'] === self::LIKED_COMMENT) {
            return "liked your comment.";
        }

        if ($data['action'] === self::COMMENTED_ON_POST) {
            return "commented on your post.";
        }

        return null;
    }

    /**
     * Get the notifier.
     *
     * @return array
     */
    public function getUserAttribute(): array
    {
        if (is_string($this->data)) {
            $data = json_decode($this->data, true);

            return $data['user'];
        }

        return $this->data['user'];
    }

    /**
     * Check if the notification has been read.
     *
     * @return bool
     */
    public function getIsReadAttribute()
    {
        return (bool) $this->read_at;
    }

    /**
     * Get the path that the user will be redirected to upon clicking the notification.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        $url = config('app.client_url');

        if (is_string($this->data)) {
            $data = json_decode($this->data, true);

            return $url . $data['url'];
        }

        return $url . $this->data['url'];
    }
}
