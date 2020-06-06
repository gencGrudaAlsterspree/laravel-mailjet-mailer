<?php

namespace WizeWiz\MailjetMailer\Listeners;

use WizeWiz\MailjetMailer\Events\EmailError;
use WizeWiz\MailjetMailer\Events\EmailSend;

class MailjetMailerEventSubscriber {

    public function onSend(EmailSend $event) {}

    public function onError(EmailError $event) {
        // @todo: initiate backup / warning
    }

    public function subscribe($events) {
        $subscriber = self::class;

        $events->listen(EmailSend::class, "{$subscriber}@onSend");
        $events->listen(EmailError::class, "{$subscriber}@onError");
    }
}