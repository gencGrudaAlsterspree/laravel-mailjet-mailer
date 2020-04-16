<?php

namespace WizeWiz\MailjetMailer\Listeners;

use WizeWiz\MailjetMailer\Events\WebhookBlockedEvent;
use WizeWiz\MailjetMailer\Events\WebhookBounceEvent;
use WizeWiz\MailjetMailer\Events\WebhookClickEvent;
use WizeWiz\MailjetMailer\Events\WebhookEvent;
use WizeWiz\MailjetMailer\Events\WebhookOpenEvent;
use WizeWiz\MailjetMailer\Events\WebhookSentEvent;
use WizeWiz\MailjetMailer\Events\WebhookSpamEvent;
use WizeWiz\MailjetMailer\Events\WebhookUnknownEvent;
use WizeWiz\MailjetMailer\Events\WebhookUnsubEvent;
use Illuminate\Support\Facades\Log;

class MailjetWebhookEventSubscriber {

    public function onOpen(WebhookEvent $event) {
        Log::info('MailjetWebhook::onOpen');
        Log::info(json_encode($event->data));
    }

    public function onClick(WebhookEvent $event) {
        Log::info('MailjetWebhook::onClick');
        Log::info(json_encode($event->data));
    }

    public function onBounce(WebhookEvent $event) {
        Log::info('MailjetWebhook::onBounce');
        Log::info(json_encode($event->data));
    }

    public function onSpam(WebhookEvent $event) {
        Log::info('MailjetWebhook::onSpam');
        Log::info(json_encode($event->data));
    }

    public function onBlocked(WebhookEvent $event) {
        Log::info('MailjetWebhook::onBlocked');
        Log::info(json_encode($event->data));
    }

    public function onUnsub(WebhookEvent $event) {
        Log::info('MailjetWebhook::onUnsub');
        Log::info(json_encode($event->data));
    }

    public function onSent(WebhookEvent $event) {
        Log::info('MailjetWebhook::onSent');
        Log::info(json_encode($event->data));
    }

    public function onUnknown(WebhookEvent $event) {
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
        $events->listen(WebhookBlockedEvent::class, "{$subscriber}@onBlocked");
        $events->listen(WebhookBounceEvent::class, "{$subscriber}@onBounce");
        $events->listen(WebhookClickEvent::class, "{$subscriber}@onClick");
        $events->listen(WebhookOpenEvent::class, "{$subscriber}@onOpen");
        $events->listen(WebhookSpamEvent::class, "{$subscriber}@onSpam");
        $events->listen(WebhookSentEvent::class, "{$subscriber}@onSent");
        $events->listen(WebhookUnsubEvent::class, "{$subscriber}@onUnsub");
        $events->listen(WebhookUnknownEvent::class, "{$subscriber}@onUnknown");
    }
}