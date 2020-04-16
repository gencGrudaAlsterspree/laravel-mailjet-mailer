<?php

namespace WizeWiz\MailjetMailer\Exceptions\Webhook;

class InvalidEventException extends WebhookException {

    protected $response = 'invalid-event-name';

}