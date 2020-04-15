<?php

namespace WizeWiz\MailjetMailer\Models;

use Illuminate\Database\Eloquent\Model;

class MailjetWebhookEvent extends Model {

    protected $table = 'mailjet_webhook_events';
    public $timestamps = true;

    protected $fillable = [
        'mailjet_request_id',
        'mailjet_id',
        'mailjet_uuid',
        'event',
        'time',
        'data'
    ];

    protected $casts = [
        'time' => 'datetime',
        'data' => 'array'
    ];

    /**
     * Update event for message model.
     */
    protected static function boot() {
        static::saved(function($model) {
            try {
                // try to update the model
               $MailjetMessage = $model->mailjet_message;
               if($MailjetMessage) {
                   // @todo: check sequence order, e.g. sent before open, before click., etc.
                   $MailjetMessage->update(['delivery_status' => $model->event]);
               }
            } catch(\Exception $e) {}
        });
        parent::boot();
    }

    // belongs to
    public function mailjet_message() {
        return $this->belongsTo(MailjetMessage::class, 'mailjet_id', 'mailjet_id');
    }

    public function mailjet_request() {
        return $this->belongsTo(MailjetRequest::class);
    }
}