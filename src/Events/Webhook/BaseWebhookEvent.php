<?php

namespace WizeWiz\MailjetMailer\Events\Webhook;

use WizeWiz\MailjetMailer\Models\MailjetWebhookEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseWebhookEvent implements ShouldBroadcastNow {
    use Dispatchable, SerializesModels;

    public $data;

    const EVENT_BLOCKED = 'blocked';
    const EVENT_BOUNCE = 'bounce';
    const EVENT_CLICK = 'click';
    const EVENT_OPEN = 'open';
    const EVENT_SENT = 'sent';
    const EVENT_SPAM = 'spam';
    const EVENT_UNSUB = 'unsub';

    const EVENT_NONE = 'none';
    const EVENT_WAITING = 'waiting';

    /**
     * WebhookEvent constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;
        $this->saveEvent();
    }

    /**
     * Save event. MailjetWebhookEvent `saved` should update the MailjetMessage::delivery_status
     */
    protected function saveEvent() {
        try {
            MailjetWebhookEvent::create([
                'mailjet_request_id' => isset($this->data['CustomID']) ? $this->data['CustomID'] : null,
                'mailjet_id' => $this->data['MessageID'],
                'mailjet_uuid' => $this->data['Message_GUID'],
                'event' => $this->data['event'],
                'time' => $this->data['time'],
                'data' => $this->data
            ]);
        // silently fail
        } catch(\Exception $e) {
            Log::info('in BaseWebhookEvent exception?');
            var_dump($e->getMessage());
            var_dump($e->getFile() . ' @ ' . $e->getCode());
        }
    }

    /**
     * @return array|\Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn() {
        return [];
    }

}