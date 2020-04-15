<?php

namespace WizeWiz\MailjetMailer\Controllers;

use App\Http\Controllers\Controller;
use WizeWiz\MailjetMailer\Events\{
    WebhookClickEvent,
    WebhookUnknownEvent,
    WebhookUnsubEvent,
    WebhookOpenEvent,
    WebhookSentEvent,
    WebhookSpamEvent,
    WebhookBounceEvent,
    WebhookBlockedEvent
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

/**
 * Mailjet Webhook Client
 * @events open, click, bounce, spam, blocked, unsub, sent
 * @package App\Http\Controllers\App\Mailjet
 */
class WebhookClient extends Controller {

    const DATA_DEFAULTS = [
        'event' => 'unknown',
        'Payload' => ''
    ];

    const VALID_EVENTS = ['bounce', 'blocked', 'click', 'open', 'spam', 'sent', 'unsub'];

    const RESPONSE_ERROR = 'ok-but-error';
    const RESPONSE_OK = 'ok';
    const EVENT_UNKNOWN = 'unknown';
    const EVENT_INVALID = 'invalid';

    const EVENT_BLOCKED = 'blocked';
    const EVENT_BOUNCE = 'bounce';
    const EVENT_CLICK = 'click';
    const EVENT_OPEN = 'open';
    const EVENT_SENT = 'sent';
    const EVENT_SPAM = 'spam';
    const EVENT_UNSUB = 'unsub';

    /**
     * Centralized call, e.g. https://domain.com/api/mailjet/webhook
     * @param Request $Request
     * @return mixed
     */
    public function index(Request $Request) {
        /*
         * @todo: Mailjet does not seem to send a token to verify the payload.
            $headers = collect($Request->header())->transform(function ($item) {
                return $item[0];
            });
         */

        try {
            return $this->handleEvents($Request->all());
        } catch(\Exception $e) {
            //  @todo: we should count the many repeats to not flood the server. Mailjet will try to resend the request
            //          every 30s for the next 24h.
            return response(static::RESPONSE_ERROR, 500);
        }
    }

    /**
     * Handle multiple events.
     * @param array $events
     *
     */
    public function handleEvents(array $events) {
        foreach($events as $event) {
            // just skip this event if it wasn't send by us.
            if($this->verifyEvent($event) === false) {
                continue;
            }
            $event_name = isset($event['event']) ? $event['event'] : static::EVENT_UNKNOWN;
            $response = $this->onEvent($event_name, $event);
        }
        // unknown or 1 event.
        switch(count($events)) {
            case 0:
                return response(static::EVENT_UNKNOWN, 200);
                break;
            case 1:
                return $response;
                break;
        }
        // multiple events, just return 'ok'.
        return response(static::RESPONSE_OK, 200);
    }

    /**
     * Direct event call, e.g. https://domain.com/api/mailjet/webhook/event
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters) {
        try {
            $events = isset($parameters[0]) ? $parameters[0] : [];
            return $this->handleEvents((array)$events);
        } catch(\Exception $e) {
            //  @todo: we should count the many repeats to not flood the server. Mailjet will try to resend the request
            //          every 30s for the next 24h.
            return response(static::RESPONSE_ERROR, 500);
        }
    }

    /**
     * Verify payload.
     * @param array $event
     * @return bool
     */
    protected function verifyEvent($event) : bool {
        if(empty($event) || !is_array($event)) {
            Log::info('unable to verify event content.');
            return false;
        }
        // verify event refers to a request made by us.
        if(isset($event['CustomID'])) {
            if(MailjetRequest::whereId($event['CustomID'])->count() === 0) {
                Log::info('unable to verify payload.');
                return false;
            }
        }
        if($this->validEvent($event['event']) === false) {
            Log::info('unable to validate event.');
            return false;
        }
        return true;
    }

    /**
     * Is valid event.
     * @param $event
     * @return bool
     */
    protected function validEvent($event) {
        return in_array($event, static::VALID_EVENTS);
    }


    /**
     * Do something when payload is invalid.
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    protected function invalidPayload() {
        try {
            // for exception sake ..
            throw new \Exception('MailjetWebhookClient: invalid payload');
        } catch(\Exception $e) {
            return response('INVALID_PAYLOAD', 200);
        }
    }

    /**
     * Unexpected event was called.
     * @param $data
     * @param $event_expected
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    protected function invalidEvent($event_called, $event_expected) {
        try {
            // for exception sake ..
            throw new \Exception("MailjetWebhook: Received invalid event {$event_called}, expected event {$event_expected}.");
        } catch(\Exception $e) {
            return response(static::EVENT_INVALID, 200);
        }
    }

    /**
     * On open event.
     * @param $event
     * @param array $data
     * @param bool $direct_call
     * @return
     */
    public function onEvent($event, array $data, $direct_call = true) {
        // trigger the event.
        $this->triggerEvent($event, $data);
        // we return 200 because we don't want Mailjet to repeat this every 30s for 24h!
        return response($event, 200);
    }

    /**
     * Trigger event.
     * @param $event
     * @param array $data
     */
    protected function triggerEvent($event, array $data) {
        $event_class = "WizeWiz\MailjetMailer\Events\Webhook".ucfirst($event)."Event";
        if(class_exists($event_class)) {
            $event_object = new $event_class($data);
        }
        else {
            $event_object = new WebhookUnknownEvent($data);
        }

        event($event_object);
    }
}
