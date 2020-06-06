<?php

namespace WizeWiz\MailjetMailer\Models;

use Illuminate\Database\Eloquent\Model;
use WizeWiz\EnhancedNotifications\Notifications\Concerns\Notifier;
use WizeWiz\EnhancedNotifications\Notifications\Contracts\Notifies;
use WizeWiz\MailjetMailer\Concerns\HasWebhookEvents;
use WizeWiz\MailjetMailer\Events\Webhook\WebhookEvent;

class MailjetMessage extends Model implements Notifies {

    use Notifier,
        HasWebhookEvents;

    const STATUS_NONE = 'none';
    const STATUS_PENDING = 'pending';

    protected $table = 'mailjet_messages';
    public $timestamps = true;

    protected $fillable = [
        'mailjet_request_id',
        'mailjet_messageble_type',
        'mailjet_messageble_id',
        'email',
        'mailjet_id',
        'mailjet_uuid',
        'mailjet_href',
        'mailjet_template_id',
        'template_name',
        'version',
        'success',
        'status',
        'delivery_status',
        'sandbox',
    ];

    protected $casts = [
        'success' => 'boolean',
        'sandbox' => 'boolean',
    ];

    public function __construct(array $attributes = []) {
        parent::__construct($this->withDefaults($attributes));
    }

    /**
     *
     * @param array $attributes
     * @return array
     */
    protected function withDefaults(array $attributes) : array {
        return array_merge([
            'status' => static::STATUS_NONE,
            'delivery_status' => WebhookEvent::EVENT_NONE
        ], $attributes);
    }


        /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mailjet_request() {
        return $this->belongsTo(MailjetRequest::class);
    }

    /**
     * Morphable
     */
    public function mailjet_messageble() {
        return $this->morphTo();
    }

    /**
     * @return mixed|null
     */
    public function latestEvent() {
        return $this->mailjet_webhook_events()->orderBy('time', 'desc')->first();
    }

    public static function customId($id) {
        return static::where('custom_id', $id)->first();
    }

    public static function mailjetId($id) {
        return static::where('mailjet_id', $id)->first();
    }

    public static function mailjetUuid($uuid) {
        return static::where('mailjet_uuid', $uuid)->first();
    }

}