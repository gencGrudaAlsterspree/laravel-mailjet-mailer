<?php

namespace WizeWiz\MailjetMailer\Exceptions\Webhook;

class InvalidEventDataException extends WebhookException {

    protected $response = 'invalid-event-data';

}