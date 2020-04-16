<?php

namespace WizeWiz\MailjetMailer;

use Illuminate\Support\Facades\Log;
use Mailjet\Response as MailjetResponse;
use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

/**
 * @todo: make queueable
 * @todo: create queable job
 * Class MailerResponse
 * @package App\Library\Mailjet
 */
abstract class MailerResponse {

    const HTTP_STATUS_OK = 200;

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    protected $Request;
    protected $Response;

    protected $success;
    protected $http_status;
    protected $messages_count;
    protected $messages_data;
    protected $api_version;

    protected $notifiables = [];

    protected $messages = [];
    protected $errors = [];

    /**
     * MailerResponse constructor.
     * @param MailjetResponse $Response
     * @param string $api_version
     */
    public function __construct(MailjetRequest $Request, MailjetResponse $Response, $api_version) {
        // set original MailjetResponse.
        $this->Request= $Request;
        $this->Response = $Response;

        // set properties
        $this->setProperties();
        // set api version
        $this->api_version = $api_version;
    }

    /**
     * Inspect response data according to Send API v3.1
     * @return bool
     * @throws \Exception
     * @todo: create models instead.
     */
    public function analyze() {
        // if(empty($this->messages_data)) {
            // @todo: custom exception
        //    throw new \Exception(get_class($this) . ' needs to be constructed.');
        // }
        // structure is valid
        // @todo: let MailerResponse3/31 decide structure validity
        // if(!$this->isValid()) {
            // @todo: custom exception
        //    throw new \Exception('JSON structure from Mailjet/Response is not valid.');
        // }

        // if http status code is OK and message was success
        if($this->http_status === static::HTTP_STATUS_OK && $this->success) {
           // $this->messages = $this->setSuccess();
           // $this->attachNotifiablesToMessages();
            return true;
        }
        // handle errors
        else {
           // $this->errors = $this->setErrors();
            return false;
        }
    }

    /**
     * Attach notifiables to messages (polymorphic).
     */
    public function attachNotifiablesToMessages() {
        if(empty($this->messages) === false) {
            foreach ($this->messages as $index => $message) {
                if (isset($this->notifiables[$message->email])) {
                    $notifiable = $this->notifiables[$message->email];
                    if ($notifiable instanceof MailjetMessageable) {
                        try {
                            var_dump($message);
                            // $notifiable->mailjet_messages()->save($message);
                        } catch(\Exception $e) {
                            Log::info('$notifiable mailjet_messages()->save');
                            Log::info($e->getMessages());
                        }
                    }
                }
            }
        }
    }

    /**
     * Set response properies.
     */
    protected abstract function setProperties() : void;

    /**
     * Is response structure valid.
     * @return bool
     */
    public abstract function isValid() : bool;

    /**
     * Set errors.
     * @return array
     */
    protected abstract function setSuccess();

    /**
     * Set success and create MailerMessage models.
     */
    protected abstract function setErrors();

    /**
     * Return status.
     * @return string
     */
    public function status() : string {
        return $this->http_status;
    }

    /**
     * Return messages count.
     * @return mixed
     */
    public function count() {
        return $this->messages_count;
    }

    /**
     * Return messages data.
     */
    public function data() {
       return $this->messages_data;
    }

    /**
     * If call was success.
     * @return bool
     */
    public function success() {
        return $this->success;
    }

    /**
     * If call returned errors.
     * @return bool
     */
    public function error() {
        return $this->http_status === static::STATUS_ERROR;
    }

    /**
     * Return errors.
     */
    public function getErrors() {
       return $this->errors;
    }

    /**
     * Return messages from Mailjet\Response.
     * @return null|array
     */
    public function getMessages() {
        if($this->success()) {
            return $this->Response->getBody();
        }
    }

    /**
     * Array representation.
     */
    public function toArray() : array {
        return [
            'status' => $this->status(),
            'count' => $this->count(),
            'data' => $this->data(),
            'success' => $this->success(),
            'error' => $this->error(),
            'errors' => $this->getErrors()
        ];
    }

}