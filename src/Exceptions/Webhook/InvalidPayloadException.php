<?php

namespace WizeWiz\MailjetMailer\Exceptions\Webhook;

/**
 * Event payload could not be verified. The CustomID of the event given will be verified with the request ID. If no
 * match was found, we can assume we never send any request with given ID.
 *
 * Mailjet does not seem to send a token to verify the payload, e.g. will not contain any payload header information.
    $headers = collect($Request->header())->transform(function ($item) {
        return $item[0];
    });
 *
 * Class InvalidPayloadException
 * @package WizeWiz\MailjetMailer\Exceptions\Webhook
 */
class InvalidPayloadException extends WebhookException {

    protected $response = 'invalid-event-payload';

}