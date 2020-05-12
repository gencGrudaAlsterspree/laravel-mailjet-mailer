<?php

namespace WizeWiz\MailjetMailer\Tests\Mailjet;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Mailjet\Client;
use Mailjet\Config;
use Mailjet\Request;
use Mailjet\Response;

class FakeRequest extends Request {

    protected $method;
    protected $url;
    protected $filters;
    protected $body;
    protected $auth;
    protected $type;
    protected $requestOptions = [];

    /**
     * Build a new Http request
     * @param array  $auth    [apikey, apisecret]
     * @param string $method  http method
     * @param string $url     call url
     * @param array  $filters Mailjet resource filters
     * @param array  $body    Mailjet resource body
     * @param string $type    Request Content-type
     */
    public function __construct($auth, $method, $url, $filters, $body, $type, array $requestOptions = []) {
        GuzzleClient::__construct(['defaults' => [
            'headers' => [
                'user-agent' => Config::USER_AGENT . phpversion() . '/' . Client::WRAPPER_VERSION
            ]
        ]]);
        $this->type = $type;
        $this->auth = $auth;
        $this->method = $method;
        $this->url = $url;
        $this->filters = $filters;
        $this->body = $body;
        $this->requestOptions = $requestOptions;
    }

    /**
     * Trigger the actual call
     * TODO: DATA API
     * @param $call
     * @return Response the call response
     */
    public function call($call) {
        $payload = [
            'query' => $this->filters,
            ($this->type === 'application/json' ? 'json' : 'body') => $this->body,
        ];

        $authArgsCount = count($this->auth);
        $headers = [
            'content-type' => $this->type
        ];

        if ($authArgsCount > 1) {
            $payload['auth'] = $this->auth;
        } else {
            $headers['Authorization'] = 'Bearer ' . $this->auth[0];
        }

        $payload['headers'] = $headers;

        if ((! empty($this->requestOptions)) && (is_array($this->requestOptions))) {
            $payload = array_merge_recursive($payload, $this->requestOptions);
        }

        // we will never make the call, just create a fake Guzzle response and mimic the response from the data supplied
        // in the request.
        // @todo: add parameter to mimic errors.
        $response = new Guzzle\FakeResponse($this);
        return new Response($this, $response);
    }

    /**
     * Filters getters
     * @return array Request filters
     */
    public function getFilters() {
        return $this->filters;
    }

    /**
     * Http method getter
     * @return string Request method
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Call Url getter
     * @return string Request Url
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Request body getter
     * @return array request body
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Auth getter. to discuss
     * @return string Request auth
     */
    public function getAuth() {
        return $this->auth;
    }
}