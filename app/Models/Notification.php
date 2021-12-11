<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;
use App\Traits\HasUuid;

class Notification extends DatabaseNotification
{
    use HasUuid;

    const FOLLOWED = 1;
    const LIKED_POST = 2;
    const LIKED_COMMENT = 3;
    const COMMENTED_ON_POST = 4;

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
        'path',
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
        $message = null;

        if ($this->data['action'] === self::FOLLOWED) {
            $message = "{$this->data['user']['name']} followed you.";
        }
        
        if ($this->data['action'] === self::LIKED_POST) {
            $message = "{$this->data['user']['name']} liked your post.";
        }

        if ($this->data['action'] === self::LIKED_COMMENT) {
            $message = "{$this->data['user']['name']} liked your comment.";
        }

        if ($this->data['action'] === self::COMMENTED_ON_POST) {
            $message = "{$this->data['user']['name']} commented on your post.";
        }

        return $message;
    }

    /**
     * Get the notifier.
     *
     * @return array
     */
    public function getUserAttribute(): array
    {
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
    public function getPathAttribute()
    {
        return config('app.client_url') . $this->data['path'];
    }
}
