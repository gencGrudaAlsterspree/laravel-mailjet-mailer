<?php

namespace WizeWiz\MailjetMailer;

use App\Model\MailjetMessage;
use Mailjet\Response as MailjetResponse;
use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;

class MailerResponse31 extends MailerResponse {

    /**
     * MailerResponse31 constructor.
     * @param MailjetResponse $Response
     */
    public function __construct(MailjetRequestable $Requests, MailjetResponse $Response) {
        // pass on response data and set Send API version
        parent::__construct($Requests, $Response, Mailer::VERSION_31);
    }

    /**
     * Set response properties.
     */
    protected function setProperties(): void {
        $this->success = $this->Response->success();
        $this->http_status = $this->Response->getStatus();
        $this->messages_count = $this->Response->getCount();
        $this->messages_data = $this->Response->getData();
    }

    /**
     * Is response structure valid
     * @return bool
     */
    public function isValid() : bool {
        return isset($this->messages_data['Messages']) && is_array($this->messages_data['Messages']);
    }

    /**
     * Handle response with errors.
     * @param Mailer|null $Mailer
     */
    protected function setErrors() {
        // @todo: Implement setErrors() method.
    }

    /**
     * Handle response as success and create MailerMessage models.
     * @param Mailer|null $Mailer
     * @return array
     */
    protected function setSuccess(){
        $messages = [];
        foreach($this->messages_data['Messages'] as $mailjet_message) {
            // set message by custom id
            if(is_array($mailjet_message['To'])) {
                foreach ($mailjet_message['To'] as $recipient) {
                    $recipient = $this->validateMessage($recipient);
//                    $MailjetMessage = $this->Request->mailjet_messages()->save([
//
//                    ]);
//                    array_push($messages, $MailjetMessage);
                }
            }
        }
        return $messages;
    }

    /**
     * Validate recipient data.
     * @param array $recipient
     * @return array
     */
    protected function validateMessage(array $recipient) : array {
        // if sandbox, generate custom id's
        if($this->Request->isSandboxed()) {
            $uuid = uniqid('sbx-');
            $id = hexdec($uuid);
            $recipient = array_merge($recipient, [
                'MessageID' => $id,
                'MessageUUID' => $uuid,
                'MessageHref' => 'sanboxed'
            ]);
        }
        // return corrected recipient
        return $recipient;
    }

}