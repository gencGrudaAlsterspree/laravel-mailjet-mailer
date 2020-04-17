<?php

namespace WizeWiz\MailjetMailer\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use WizeWiz\MailjetMailer\Events\{
    WebhookUnknownEvent
};
use Illuminate\Http\Request;
use WizeWiz\MailjetMailer\Exceptions\Webhook\InvalidEventDataException;
use WizeWiz\MailjetMailer\Exceptions\Webhook\InvalidEventException;
use WizeWiz\MailjetMailer\Exceptions\Webhook\InvalidPayloadException;
use WizeWiz\MailjetMailer\Exceptions\Webhook\WebhookException;
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

    const RESPONSE_ERROR = 'error';
    const RESPONSE_OK = 'ok';
    const EVENT_NAME_UNKNOWN = 'unknown';

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
        return $this->handleEvents($Request->all());
    }

    /**
     * Direct event call, e.g. https://domain.com/api/mailjet/webhook/event-name
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters) {
        $events = isset($parameters[0]) ? $parameters[0] : [];
        return $this->handleEvents((array)$events);
    }

    /**
     * Handle multiple events.
     * @param array $events
     *
     */
    public function handleEvents(array $events) {
        try {
            foreach ($events as $event) {
                // just skip this event if it wasn't send by us.
                if ($this->verifyEvent($event) === false) {
                    continue;
                }
                $event_name = isset($event['event']) ? $event['event'] : static::EVENT_NAME_UNKNOWN;
                $response = $this->onEvent($event_name, $event);
            }
            switch (count($events)) {
                // unknown event ..
                case 0:
                    return $this->response(static::EVENT_NAME_UNKNOWN);
                    break;
                // one event, all good ..
                case 1:
                    return $response;
                    break;
            }
        } catch(WebhookException $e) {
            return $e->response();
        } catch(\Throwable $e) {
            // return default response.
            return $this->response(static::RESPONSE_ERROR);
        }
        // multiple events, just return 'ok'.
        return $this->response(static::RESPONSE_OK);
    }


    /**
     * Verify payload.
     * @param array $event
     * @return bool
     */
    protected function verifyEvent($event) : bool {
        if(
            empty($event) ||
            !is_array($event) ||
            // check if event name was given.
            (isset($event['event']) && empty($event['event'])) ||
            // check if message id was given.
            (isset($event['MessageID']) && empty($event['MessageID'])) ||
            // check if custom id was given.
            (isset($event['CustomID']) && empty($event['CustomID']))
        ) {
            throw new InvalidEventDataException();
        }
        // very event name is a valid event.
        if($this->validEvent($event['event']) === false) {
            throw new InvalidEventException();
        }
        // verify event refers to a request made by us.
        if(MailjetRequest::whereId($event['CustomID'])->count() === 0) {
            throw new InvalidPayloadException();
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
     * On open event.
     * @param $event
     * @param array $data
     * @param bool $direct_call
     * @return
     */
    public function onEvent($event, array $data, $direct_call = true) {
        // trigger the event.
        $this->triggerEvent($event, $data);
        // return ok
        return $this->response(static::RESPONSE_OK . '-' . $event);
    }

    /**
     * Trigger event.
     * @param $event
     * @param array $data
     */
    protected function triggerEvent($event, array $data) {
        $event_class = "WizeWiz\MailjetMailer\Events\Webhook".ucfirst($event)."Event";
            $event_object = class_exists($event_class) ?
                new $event_class($data) :
                new WebhookUnknownEvent($data);

        event($event_object);
    }

    /**
     * Return response.
     * @param $msg
     * @param int $code
     * @return Response
     */
    protected function response($msg, $code = Response::HTTP_OK) {
        return response($msg, $code);
    }
}
