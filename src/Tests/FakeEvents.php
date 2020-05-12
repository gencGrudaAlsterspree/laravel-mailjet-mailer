<?php

namespace WizeWiz\MailjetMailer\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use WizeWiz\MailjetMailer\Models\MailjetMessage;

class FakeEvents {

    protected $default_event_data = [
        'time' => null,
        'mj_campaign_id' => 7257,
        'mj_contact_id' => 4,
        'customcampaign' => '',
        'Payload' => ''
    ];

    protected $default_event_message_data = [
        'email' => 'api@fakemail.com',
        'CustomID' => '',
        'MessageID' => 19421777835146490,
        'mj_message_id' => '19421777835146490',
        'Message_GUID' => ''
    ];

    protected function getFakeEventDefault($key) {
        switch($key) {
            case 'MessageID':
                return random_int(1000000000, 2000000000);
                break;
            case 'Message_GUID':
            case 'CustomID':
                return (string)Str::uuid();
                break;
            case 'email':
                return 'receiver@fakemail.local';
                break;
        }
    }


    /**
     * Generate fake event data from a valid MailjetMessage.
     * @param $event_name
     * @param $message_id
     * @param bool $encode
     * @return json|array
     * @throws \Exception
     */
    public function generateFromMessage($event_name, $message_id, $encode = false) {
        if(is_int($message_id)) {
            $message = MailjetMessage::find($message_id);
        }
        if($message_id instanceof MailjetMessage) {
            $message = $message_id;
        }
        if(empty($message)) {
            throw new Exception('unable to find message with message_id: ' . $message_id);
        }
        $event_data = $this->generateFakeEventData($event_name, [
            'time' => now()->timestamp,
            'email' => $message->email,
            'MessageID' => $message->mailjet_id,
            'mj_message_id' => "{$message->mailjet_id}",
            'Message_GUID' => $message->mailjet_uuid,
            'CustomID' => $message->mailjet_request_id
        ]);
        return $encode ? json_encode($event_data) : $event_data;
    }

    /**
     *
     */
    public function generateFromMessages($event_name, $messages, $encode = false) {
        if(!is_iterable($messages)) {
            throw new \Exception('$messages need to be iterable.');
        }
        $events = [];
        foreach($messages as $message) {
            $event_data = $this->generateFromMessage($event_name, $message, $encode);
            array_push($events, isset($event_data[0]) ? $event_data[0] : $event_data);
        }
        return $events;
    }

    public function generateMultipleFromMessages(array $event_names, $messages, $encode = false) {
        if(!is_iterable($messages)) {
            throw new \Exception('$messages need to be iterable.');
        }
        $events = [];
        foreach($messages as $message) {
            $multiple_events = $this->generateMultipleFromMessage($event_names, $message, $encode);
            $events = array_merge($events, $multiple_events);
        }
        return $events;
    }

    /**
     * Generate multiple fake events from a valid MailjetMessage.
     * @param array $event_names
     * @param $message_id
     * @param bool $encode
     * @return json|array
     * @throws \Exception
     */
    public function generateMultipleFromMessage(array $event_names, $message_id, $encode = false) {
        $events = [];
        foreach($event_names as $event_name) {
            $event_data = $this->generateFromMessage($event_name, $message_id, $encode);
            array_push($events, isset($event_data[0]) ? $event_data[0] : $event_data);
        }
        return $events;
    }

    /**
     * Generate fake event data.
     * @source https://dev.mailjet.com/email/guides/webhooks/
     * @param string $event_name
     * @return array
     */
    public function generateFakeEventData($event_name, array $options = [], array $unset = []) {
        $event_data = array_merge($this->default_event_data, [
            'email' => $this->getFakeEventDefault('email'),
            'MessageID' => $this->getFakeEventDefault('MessageID'),
            'Message_GUID' => $this->getFakeEventDefault('Message_GUID'),
            'CustomID' => $this->getFakeEventDefault('CustomID'),
        ]);
        $event_data = array_merge($event_data, $options);
        switch ($event_name) {
            case 'unknown-event':
                $event_data = array_merge($event_data, [
                    'event' => 'event-does-not-exist'
                ]);
                break;
            case 'invalid-event-data':
                $event_data = array_merge($event_data, [
                    'event' => 'sent'
                ]);
                $unset = [
                    'CustomID'
                ];
                break;
            case 'invlid-event-payload':
                $event_data = array_merge($event_data, [
                    'event' => 'sent',
                    'CustomID' => 'some-custom-id-to-pass-the-valid-data-test',
                ]);
                break;
            case 'sent':
                $event_data = array_merge($event_data, [
                    'event' => $event_name,
                    'smtp_reply' => 'sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)'
                ]);
                break;
            case 'open':
                $event_data = array_merge($event_data, [
                    'event' => $event_name,
                    'ip' => '127.0.0.1',
                    'geo' => 'US',
                    'agent' => 'Mozilla/5.0 (Windows NT 5.1; rv:11.0) Gecko Firefox/11.0',
                ]);
                break;
            case 'click';
                $event_data = array_merge($event_data, [
                    'event' => $event_name,
                    "url" => "https://mailjet.com",
                    'ip' => '127.0.0.1',
                    'geo' => 'DE',
                    'agent' => 'Mozilla/5.0 (Windows NT 5.1; rv:11.0) Gecko Firefox/11.0',
                ]);
                break;
            case 'bounce':
                $event_data = array_merge($event_data, [
                    'event' => $event_name,
                    'blocked' => false,
                    'hard_bounce' => true,
                    'error_related_to' => 'recipient',
                    'error' => 'user unknown',
                    'comment' => 'Host or domain name not found. Name service error for name=lbjsnrftlsiuvbsren.com type=A: Host not found',
                ]);
                break;
            case 'blocked':
                $event_data = array_merge($event_data, [
                    'event' => $event_name,
                    "error_related_to" => "recipient",
                    "error" => "user unknown"
                ]);
                break;
            case 'spam':
                $event_data = array_merge($event_data, [
                    'event' => $event_name,
                    "source" => "JMRPP"
                ]);
                break;
            case 'unsub':
                $event_data = array_merge($event_data, [
                    'event' => $event_name,
                    "mj_list_id" => 1,
                    "ip" => "127.0.0.1",
                    "geo" => "FR",
                    "agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36"
                ]);
                break;
            default:
                throw new \Exception(static::class . ': invaid call: ' . $event_name);
        }

        // unset any keys
        foreach($unset as $key) {
            if(array_key_exists($key, $event_data)) {
                unset($event_data[$key]);
            }
        }

        return [$event_data];
    }

    /**
     * Create fake request.
     * @param $event_name
     * @return Request
     */
    public function getFakeEventRequest($event_name, array $options = [], array $unset = []) {
        $data = $this->generateFakeEventData($event_name, $options, $unset);
        return Request::create('/api/mailjet/webhook', 'POST', $data);
    }
}