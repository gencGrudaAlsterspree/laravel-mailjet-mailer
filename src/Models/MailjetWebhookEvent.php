<?php

namespace WizeWiz\MailjetMailer\Models;

use Illuminate\Database\Eloquent\Model;
use WizeWiz\MailjetMailer\Events\Webhook\WebhookEvent;

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
        static::saved(function(MailjetWebhookEvent $EventModel) {
            try {
                // try to update the model
               $MailjetMessage = $EventModel->mailjet_message;
               if(!empty($MailjetMessage)) {
                   $current_status = $MailjetMessage->delivery_status;
                   $updateable = false;

                   switch($EventModel->event) {
                       case WebhookEvent::EVENT_CLICK:
                           if($current_status === WebhookEvent::EVENT_OPEN ||
                              $current_status === WebhookEvent::EVENT_SENT) {
                                $updateable = true;
                           }
                           break;
                       case WebhookEvent::EVENT_OPEN:
                           if($current_status !== WebhookEvent::EVENT_CLICK) {
                               $updateable = true;
                           }
                           break;
                       case WebhookEvent::EVENT_SENT:
                           if($current_status === WebhookEvent::EVENT_WAITING) {
                              $updateable = true;
                           }
                           break;
                       case WebhookEvent::EVENT_BLOCKED:
                       case WebhookEvent::EVENT_SPAM:
                       case WebhookEvent::EVENT_BOUNCE:
                           // @todo: always update?
                           $updateable = true;
                           break;
                   }

                   if($updateable) {
                       $MailjetMessage->update(['delivery_status' => $EventModel->event]);
                   }
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