<?php

namespace WizeWiz\MailjetMailer\Tests\Mailjet\Guzzle;

use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Class FakeResponse
 * @package WizeWiz\MailjetMailer\Tests\Mailjet
 */
class FakeResponse {

    const END_POINT = 'https://api.mailjet.fake.local/v3/message/';

    protected $body;
    protected $status;

    /**
     * @var \WizeWiz\MailjetMailer\Tests\Mailjet\FakeRequest
     */
    public function __construct($Request) {
         $this->generateResponseFromRequest($Request);
    }

    public function getStatusCode() {
        return $this->status;
    }

    public function getBody($encoded = true) {
        return $encoded ? $this->body : json_decode($this->body, true);
    }

    /**
     * @param $Request
     */
    protected function generateResponseFromRequest($Request) {
        $this->status = Response::HTTP_OK;
        $messages = collect([]);
        $request_body = $Request->getBody();

        try {
            if (array_key_exists('Messages', $request_body)) {
                foreach ($request_body['Messages'] as $message) {
                    $messages->add($this->generateResponseMessage($message));
                }
            }
        } catch(\Exception $e) {
            dump($e);
        }

        $this->body = json_encode([
            'Data' => [
                'Messages' => $messages->toArray(),
                'FakeResponse' => true
            ],
            'Count' => $messages->count(), // what does Count? total messages?
            'Total' => 1 // what does Total? total pages?
        ]);
    }

    /**
     * @param array $message
     * @return array
         {
            "Status": "success",
            "CustomID": "custom-id",
            "To": [
                {
                "Email": "passenger1@mailjet.com",
                "MessageUUID": "123",
                "MessageID": 456,
                "MessageHref": "https://api.mailjet.com/v3/message/456"
                }
            ]
        }
     */
    protected function generateResponseMessage(array $message) {
        $message_body = [
            'FakeMessage' => true,
            'Status' => "success",
            'CustomID' => $message['CustomID'],
            "To" => [],
            // @note: CC and BCC seem to generate unique messages as well, just like `To`.
            // @source: https://dev.mailjet.com/email/guides/send-api-v31/
            "Cc" => [],
            "Bcc" => [],
        ];
        foreach(['To', 'Cc', 'Bcc'] as $receiver_type) {
            if(array_key_exists($receiver_type, $message)) {
                foreach ($message[$receiver_type] as $receiver) {
                    $message_body[$receiver_type][] = $this->generateUniqueFakeMessage($receiver['Email']);
                }
            }
        }
        // remove if empty.
        foreach(['Cc', 'Bcc'] as $receiver_type) {
            if (array_key_exists($receiver_type, $message_body) && empty($message_body[$receiver_type])) {
                unset($message_body[$receiver_type]);
            }
        }
        return $message_body;
    }

    /**
     * @param $email
     * @return array
     * @throws \Exception
     */
    protected function generateUniqueFakeMessage($email) : array {
        $message_id = random_int(1000000000, 2000000000);
        return [
            'Email' => $email,
            'MessageUUID' => (string) Str::uuid(),
            'MessageID' => $message_id,
            // .. for authentic sake ..
            'MessageHref' => $this->fakeMessageHref($message_id)
        ];
    }

    /**
     * Generate a fake REST message hyperlink reference.
     * @param int $message_id
     * @return string
     */
    protected function fakeMessageHref(int $message_id) : string {
        return static::END_POINT.$message_id;
    }

}