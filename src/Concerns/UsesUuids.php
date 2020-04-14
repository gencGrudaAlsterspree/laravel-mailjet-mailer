<?php

namespace WizeWiz\MailjetMailer\Concerns;

use Illuminate\Support\Str;

trait UsesUuids {

    protected static function bootUsesUuid() {
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string)Str::uuid();
            }
        });
    }

    public function getIncrementing() {
        return false;
    }

    public function getKeyType() {
        return 'string';
    }

}