<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{   
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->setAttribute('id', (string) Str::uuid());
            $model->setAttribute('slug', uniqid());
        });
    }
}