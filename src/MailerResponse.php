<?php

namespace WizeWiz\MailjetMailer;

use Mailjet\Response as MailjetResponse;
use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;

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

    protected $Requests;
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
    public function __construct(MailjetRequestable $Requests, MailjetResponse $Response, $api_version) {
        // set original MailjetResponse.
        $this->Requests = $Requests;
        $this->Response = $Response;

        // set properties
        $this->setProperties();
        // set api version
        $this->api_version = $api_version;
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
     * Return messages.
     * @return null|array
     */
    public function getMessages() {
        if($this->success()) {
            if(isset($this->messages_data['Messages'])) {
                return $this->messages_data['Messages'];
            }
        }
        return [];
    }

    /**
     * Find message by email.
     * @param $email
     * @return bool
     */
    public function getMessageByEmail($email) {
        foreach ($this->getMessages() as $index => $message) {
            foreach(['To', 'Cc', 'Bcc'] as $receiver_type) {
                if(isset($message[$receiver_type]) &&
                    ($found_index = array_search($email, array_column($message[$receiver_type], 'Email'))) !== false) {
                    return $message[$receiver_type][$found_index];
                }
            }
        }
        return false;
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