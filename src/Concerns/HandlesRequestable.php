<?php

namespace WizeWiz\MailjetMailer\Concerns;

use Mailjet\Response as MailjetLibResponse;
use WizeWiz\MailjetMailer\Collections\MailjetRequestCollection;
use WizeWiz\MailjetMailer\Events\Webhook\WebhookEvent;
use WizeWiz\MailjetMailer\MailerResponse;
use WizeWiz\MailjetMailer\Models\MailjetMessage;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

trait HandlesRequestable {

    /**
     * @var string If the message would be send into the real world.
     * @modes live, dry
     */
    private $mode = 'live';

    /**
     * @var bool
     */
    protected $sent = false;

    /**
     * If message was send.
     * @todo: maybe move to HandlesRequestable
     * @return bool
     */
    public function isSent() : bool {
        return $this->sent;
    }

    /**
     * Set mode.
     * @param $mode Modes: live | dry
     */
    public function setRequestMode($mode) {
        // @todo: check modes
        $this->mode = $mode;
        return $this;
    }

    /**
     * Return mode.
     * @return string
     */
    public function getRequestMode() : string {
        return $this->mode;
    }

    /**
     * Convert to a MailjetRequestCollection.
     *
     * @param bool $add_request Add request to the collection when converting.
     */
    public function toCollection($add_request = true) {
        if($this instanceof MailjetRequestCollection) {
            return $this;
        }
        $this->isSandboxed();
        $Collection = (new MailjetRequestCollection($this->getVersion()));
        // make sure we pass on all the info from the single request to the collection.
        $Collection->setRequestMode($this->getRequestMode());
        if($this->isSandboxed()) {
            $Collection->useSandbox();
        }
        if($this->shouldQueue()) {
            $Collection->queue($this->getQueue());
        }
        // should we add this request?
        if($add_request) {
            $Collection->add($this);
        }
        return $Collection;
    }

    /**
     * Return version.
     * @return mixed
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @see https://dev.mailjet.com/email/guides/send-api-v31/#sandbox-mode
     * Turn sandbox on (only supported in v3.1)
     * @param bool $sandbox
     * @return Mailer
     */
    public function useSandbox($sandbox = true) {
        $this->sandbox = $sandbox;
        return $this;
    }

    /**
     * Return is call will is/will be sandboxed.
     * @return bool
     */
    public function isSandboxed() : bool {
        return $this->sandbox;
    }


    /**
     * Update the request with given response.
     */
    public function updateFromResponse($error, MailerResponse $Response, MailjetLibResponse $LibResponse) {
        // request was sent
        if($error === false) {
            // mark request as sent
            $this->markAsSent();
            $data = [
                'status' => 'success',
                'success' => true
            ];
        }
        else {
            // mark as failed
            $this->markAsFailed();
            $data = [
                'status' => 'failed',
                'success' => false
            ];
        }
        // update request
        if(($updated = $this->update($data))) {
            // add delivery status for message.
            $data['delivery_status'] = WebhookEvent::EVENT_WAITING;
            // update messages
            /**
             * @var MailjetMessage $message
             */
            foreach($this->mailjet_messages as $message) {
                if(($response_message = $Response->getMessageByEmail($message->email))) {
                    $data['mailjet_id'] = $response_message['MessageID'];
                    $data['mailjet_uuid'] = $response_message['MessageUUID'];
                    $data['mailjet_href'] = $response_message['MessageHref'];

                    // @todo: this can be removed, request gathers template/variables info.
                    if($this instanceof MailjetRequest) {
                        $data['mailjet_template_id'] = $this->template_id;
                        $data['template_name'] = $this->template_name;
                    }
                }
                $message->update($data);
            }
        }
        return $updated;
    }

    /**
     * Reset request.
     * @param $status
     * @param bool $remove_events
     */
    public function resetStatus($status = self::STATUS_PREPARED) : void {
        if($this instanceof MailjetRequestCollection) {
            foreach($this->items as $Request) {
                $Request->resetStatus($status);
            }
            return;
        }

        $this->update([
            'status' => $status
        ]);
        // delete events
        $this->mailjet_webhook_events()->delete();
        // reset mesages to pending status.
        $this->mailjet_messages->each(function($Message) {
            $Message->update([
                'delivery_status' => WebhookEvent::EVENT_NONE
            ]);
        });
    }

}