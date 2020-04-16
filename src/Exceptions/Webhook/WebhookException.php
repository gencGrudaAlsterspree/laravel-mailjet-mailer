<?php


namespace WizeWiz\MailjetMailer\Exceptions\Webhook;

use Illuminate\Http\Response;
use WizeWiz\MailjetMailer\Controllers\WebhookClient;

abstract class WebhookException extends \Exception {

    protected $response_http_code = Response::HTTP_OK;
    protected $response = WebhookClient::RESPONSE_ERROR;

    /**
     * Get error response.
     * @return mixed
     */
    public function response() {
        return response($this->response, $this->response_http_code);
    }

}