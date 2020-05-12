<?php

namespace WizeWiz\MailjetMailer\Listeners;

use Illuminate\Support\Facades\Log;
use WizeWiz\MailjetMailer\Events\EmailError;
use WizeWiz\MailjetMailer\Events\EmailSend;

class MailjetMailerEventSubscriber {

    public function onSend(EmailSend $event) {
        Log::info('MailjetMailerEventSubscriber@onSend');
        var_dump($event->MailerResponse->toArray());
    }

    public function onError(EmailError $event) {
        Log::info('MailjetMailerEventSubscriber@onError');
        var_dump($event->MailerResponse->toArray());
    }

    public function subscribe($events) {
        $subscriber = self::class;

        $events->listen(EmailSend::class, "{$subscriber}@onSend");
        $events->listen(EmailError::class, "{$subscriber}@onError");
    }
}