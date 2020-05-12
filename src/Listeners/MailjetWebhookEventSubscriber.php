<?php

namespace WizeWiz\MailjetMailer\Listeners;

use WizeWiz\MailjetMailer\Events\Webhook\BaseWebhookEvent;
use WizeWiz\MailjetMailer\Events\Webhook\BlockedEvent;
use WizeWiz\MailjetMailer\Events\Webhook\BounceEvent;
use WizeWiz\MailjetMailer\Events\Webhook\ClickEvent;
use WizeWiz\MailjetMailer\Events\Webhook\OpenEvent;
use WizeWiz\MailjetMailer\Events\Webhook\SentEvent;
use WizeWiz\MailjetMailer\Events\Webhook\SpamEvent;
use WizeWiz\MailjetMailer\Events\Webhook\UnknownEvent;
use WizeWiz\MailjetMailer\Events\Webhook\UnsubEvent;
use Illuminate\Support\Facades\Log;

class MailjetWebhookEventSubscriber {

    public function onOpen(BaseWebhookEvent $event) {
        Log::info('MailjetWebhook::onOpen');
        Log::info(json_encode($event->data));
    }

    public function onClick(BaseWebhookEvent $event) {
        Log::info('MailjetWebhook::onClick');
        Log::info(json_encode($event->data));
    }

    public function onBounce(BaseWebhookEvent $event) {
        Log::info('MailjetWebhook::onBounce');
        Log::info(json_encode($event->data));
    }

    public function onSpam(BaseWebhookEvent $event) {
        Log::info('MailjetWebhook::onSpam');
        Log::info(json_encode($event->data));
    }

    public function onBlocked(BaseWebhookEvent $event) {
        Log::info('MailjetWebhook::onBlocked');
        Log::info(json_encode($event->data));
    }

    public function onUnsub(BaseWebhookEvent $event) {
        Log::info('MailjetWebhook::onUnsub');
        Log::info(json_encode($event->data));
    }

    public function onSent(BaseWebhookEvent $event) {
        Log::info('MailjetWebhook::onSent');
        Log::info(json_encode($event->data));
    }

    public function onUnknown(BaseWebhookEvent $event) {
        Log::info('MailjetWebhook::onUnknown');
        Log::info(json_encode($event->data));
    }

    /**
     * Subscribe to events with callbacks
     *
     * @param $events
     */
    public function subscribe($events) {
        $subscriber = self::class;
        // all mailjet webhook events.
        $events->listen(BlockedEvent::class, "{$subscriber}@onBlocked");
        $events->listen(BounceEvent::class, "{$subscriber}@onBounce");
        $events->listen(ClickEvent::class, "{$subscriber}@onClick");
        $events->listen(OpenEvent::class, "{$subscriber}@onOpen");
        $events->listen(SpamEvent::class, "{$subscriber}@onSpam");
        $events->listen(SentEvent::class, "{$subscriber}@onSent");
        $events->listen(UnsubEvent::class, "{$subscriber}@onUnsub");
        $events->listen(UnknownEvent::class, "{$subscriber}@onUnknown");
    }
}