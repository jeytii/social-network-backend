<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
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
        'action',
        'is_read',
        'path',
    ];

    // =============================
    // CUSTOM ATTRIBUTES
    // =============================

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
     * Get action type.
     *
     * @return int
     */
    public function getActionAttribute(): int
    {
        return $this->data['action'];
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
        return $this->data['path'];
    }
}
