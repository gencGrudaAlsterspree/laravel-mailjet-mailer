<?php

namespace WizeWiz\MailjetMailer\Listeners;

use WizeWiz\MailjetMailer\Events\Webhook\WebhookEvent;
use WizeWiz\MailjetMailer\Events\Webhook\BlockedEvent;
use WizeWiz\MailjetMailer\Events\Webhook\BounceEvent;
use WizeWiz\MailjetMailer\Events\Webhook\ClickEvent;
use WizeWiz\MailjetMailer\Events\Webhook\OpenEvent;
use WizeWiz\MailjetMailer\Events\Webhook\SentEvent;
use WizeWiz\MailjetMailer\Events\Webhook\SpamEvent;
use WizeWiz\MailjetMailer\Events\Webhook\UnknownEvent;
use WizeWiz\MailjetMailer\Events\Webhook\UnsubEvent;

class MailjetWebhookEventSubscriber {

    public function onOpen(WebhookEvent $event) {}

    public function onClick(WebhookEvent $event) {}

    public function onBounce(WebhookEvent $event) {}

    public function onSpam(WebhookEvent $event) {}

    public function onBlocked(WebhookEvent $event) {}

    public function onUnsub(WebhookEvent $event) {}

    public function onSent(WebhookEvent $event) {}

    public function onUnknown(WebhookEvent $event) {}

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