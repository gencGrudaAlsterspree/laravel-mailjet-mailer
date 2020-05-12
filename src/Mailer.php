<?php

namespace WizeWiz\MailjetMailer;

use Illuminate\Support\Facades\Log;
use Mailjet\Resources;
use Mailjet\Client as MailjetLibClient;
use Mailjet\Response as MailjetLibResponse;
use WizeWiz\MailjetMailer\Collections\MailjetRequestCollection;
use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

/**
 * Mailjet wrapper for easy mangement of the Send API
 *
 * Class Mailer
 * @package App\Library
 */
class Mailer implements Contracts\MailjetMailer {

    /**
     * @var bool
     */
    protected $debug = false;

    const VERSION_3 = 'v3';
    const VERSION_31 = 'v3.1';
    const VERSIONS = [
        'v3',
        'v3.1'
    ];
    const DEFAULT_VERSION = 'v3.1';
    const PACKAGE = 'mailjet-mailer';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var bool
     */
    protected $initialized = false;
    protected $environmentalized = false;

    protected $environment;
    protected $account;
    protected $key;
    protected $secret;
    protected $version;

    /**
     * Mailer constructor.
     */
    public function __construct(array $options = []) {
        // set environment and prepare configuration
         $this->environmentalize($options);
    }

    /**
     * Sets the environment and prepares the config in config/mailjet according to the environment.
     * @return void
     * @throws \Exception
     */
    private function environmentalize(array $options = []) : void {
        $options = array_merge([
            'environment' => config(static::PACKAGE.'.environment'),
            'account' => config(static::PACKAGE.'.account')
        ], $options);
        // set environment
        $this->environment = $options['environment'];
        // get configuration
        $config = $this->configure($options['account']);
        // initialize from config
        $this->initialize($config);
    }

    /**
     * If the mailer has been initialized.
     *
     * @return bool
     */
    public function isInitialized() {
        return $this->initialized;
    }

    /**
     * Create a new Request
     * @return MailjetRequest
     */
    public function newRequest() : MailjetRequestable {
        return MailjetRequest::make([
            'version' => $this->version === null ? static::DEFAULT_VERSION : $this->version,
        ]);
    }

    /**
     * @throws \Exception
     */
    public static function clearCache() {
        cache()->clear(static::PACKAGE . ':mailer-instance');
    }

    /**
     * Create a new Collection.
     * @return MailjetRequestCollection
     */
    public function newCollection($version = null) : MailjetRequestable {
        return MailjetRequestCollection::make($version === null ? static::DEFAULT_VERSION : $version);
    }

    /**
     * Get configuration option.
     * @param $option
     * @return null|mixed
     */
    public function getConfigOption($option) {
        if($this->initialized === false) {
            // @note: this will initializes with default options.
            $this->environmentalize();
        }
        return isset($this->config[$option]) ? $this->config[$option] : null;
    }

    /**
     * Configure E-Mail.
     * @param $account
     * @return array
     * @throws \Exception
     */
    public function configure($account) : array {
        if(empty($this->environment)) {
            // @todo: general MailjetMailerException
            throw new \Exception('environment not set.');
        }
        // get config.
        $config = config(static::PACKAGE);
        $accounts = isset($config['accounts']) ? $config['accounts'] : [];

        if(!isset($accounts[$account])) {
            // @todo: general MailjetMailerException
            throw new \Exception('unable to configure with unknown account: ' . $account);
        }

        $this->account = $account;
        return $this->config = $accounts[$account];
    }

    /**
     * Initialize MailjetMailer from config.
     * @param array $config
     */
    protected function initialize(array $config) {
        $this->key = $config['key'];
        $this->secret = $config['secret'];
        $this->version = isset($config['version']) ? $config['version'] : static::DEFAULT_VERSION;
        $this->initialized = true;
    }

    /**
     * Build Mailjet Client.
     * @param array $options
     * @return MailjetLibClient
     * @throws \Exception
     */
    protected function buildClient(array $options = [], $call = true) {
        // set default version if none given.
        if(!array_key_exists('version', $options)) {
            $options['version'] = static::DEFAULT_VERSION;
        }
        // validate version
        if(!in_array($options['version'], static::VERSIONS)) {
            // @todo: general MailjetMailerException
            throw new \Exception("unsupported version supplied: {$options['version']}");
        }

        // @debug
        if($this->debug) {
            $this->log("version: {$options['version']}");
            $this->log("auth: {$this->key} : {$this->secret}");
        }
        return $this->createMailClient($this->key, $this->secret, $call, $options);
    }

    /**
     * Create a mail client.
     */
    protected function createMailClient($key, $secret, $call, $options) {
        // build Mailjet/Client
        return new MailjetLibClient($key, $secret, $call, $options);
    }

    /**
     * Send transactional E-Mail.
     *
     * @param array $options
     * @return bool|
     * @throws \Exception
     */
    public function send(MailjetRequestable $Collection, array $options = [], $dry = false) {
        // create a collection out of single MailjetRequest.
        if(!$Collection instanceof MailjetRequestCollection) {
            $Collection = $Collection->toCollection(true);
        }
        // prepare options.
        $options = array_merge(['version' => $Collection->getVersion()], $options);
        // prepare each request.
        $Collection->prepareAll($dry);
        // if Request should be queued.
        if($Collection->shouldQueue()) {
            return $this->dispatch($Collection, $options);
        }
        // process request
        return $this->process($Collection, $options);
    }

    /**
     * Send dry request.
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function sendDry(MailjetRequestable $Requests, array $options = []) {
        // send message in dry mode.
        return $this->send($Requests, $options, true);
    }

    /**
     * Process the Request.
     * @param MailjetLibClient $MailClient
     * @param array $body
     * @return bool|MailerResponse3|MailerResponse31
     * @throws \Exception
     */
    public function process(MailjetRequestable $Requests, array $options = []) {
        // requests need to be prepared
        if($Requests->isPrepared() === false) {
            throw new \Exception('unprepared requests cannot be processed by Mailer.');
        }
        // let request build the body.
        $LibResponse = null;
        // client validations $options.
        $MailClient = $this->buildClient($options);
        // get request mode
        return $this->processRequest($MailClient, $Requests);
    }

    /**
     * Process a request with given MailClient.
     * @param $Collection
     * @param $MailClient
     * @param callable $callback
     * @return bool|MailerResponse3|MailerResponse31
     */
    protected function processRequest($MailClient, MailjetRequestable $Collection) {
        switch($Collection->getRequestMode()) {
            case 'live':
                try {
                    $body = $Collection->buildBody();
                    $LibResponse = $MailClient->post(Resources::$Email, ['body' => $body]);
                    return $this->handleResponse($Collection, $LibResponse);
                } catch(\Exception $e) {
                    var_dump($e);
                    return $this->handleInternalError($e);
                }
                break;
            default:
            case 'dry':
                try {
                    $body = $Collection->buildBody();
                    var_dump($body);
                    // version 3.1 supports sandbox
                    if ($Collection->getVersion() === static::VERSION_31 && $Collection->isSandboxed() === true) {
                        // sandbox the request
                        $Collection->useSandbox();
                        $LibResponse = $MailClient->post(Resources::$Email, ['body' => $body]);
                        return $this->handleResponse($Collection, $LibResponse);
                    }

                    $this->log('sandbox: ' . ($Collection->isSandboxed() ? 'true' : 'false'));
                    foreach($Collection as $Request) {
                        $this->log("mode: {$Collection->getRequestMode()}");
                        $this->log("request: {$Collection->id}");
                        $this->log(json_encode($Request->toArray()));
                    }
                    return true;
                }
                catch(\Exception $e) {
                    return $this->handleInternalError($e);
                }
                break;
        }
        // if nothing was returned, we can just assume the call was a failure.
        return false;
    }

    /**
     * Handle Mailjet response.
     * @param MailjetLibResponse $LibResponse
     * @return mixed
     * @throws \Exception
     * @todo: implement handleError.
     */
    protected function handleResponse(MailjetRequestable $Requests, MailjetLibResponse $LibResponse) {
        // return MailerResponse according to given version.
        switch($Requests->getVersion()) {
            case static::VERSION_3:
                $MailerResponse = new MailerResponse3($Requests, $LibResponse);
                break;
            // default v3.1
            default:
            case static::VERSION_31:
                $MailerResponse = new MailerResponse31($Requests, $LibResponse);
                break;
        }
        $error = !$MailerResponse->success();
        // trigger event.
        $event = $error ?
            new Events\EmailError($Requests, $MailerResponse) :
            new Events\EmailSend($Requests, $MailerResponse);
        event($event);
        // update request with response.
        // @todo: move update from response to EmailError/EmailSend.
        $Requests->updateFromResponse($error, $MailerResponse, $LibResponse);
        //
        return $MailerResponse;
    }

    /**
     * Handle internal errors.
     * @param \Exception $e
     */
    protected function handleInternalError(\Exception $e) {
        Log::info('handle exception error');
        Log::info($e->getMessage());
    }

    /**
     * Debug.
     * @param bool $debug
     */
    public function debug($debug = true) {
        $this->debug = $debug;
    }

    /**
     * Dispatch the job.
     * @param MailjetRequest $Requests
     * @param $options
     */
    protected function dispatch(MailjetRequestable $Requests, array $options = []) {
        // add to queue if specified
        if($Requests->shouldQueue() === false) {
            // @todo: general MailjetMailerException
            throw new \Exception('Given Request/Collection is not marked to be queued.');
        }
        // should queue each request.
        if($Requests->shouldQueueEach()) {
            $dispatched_jobs = collect();
            foreach($Requests as $Request) {
                $dispatched_jobs->add(dispatch($Request->makeJob($options)));
            }
            return $dispatched_jobs;
        }
        // dipatch job
        $Job = $Requests->makeJob($options);
        return dispatch($Job);
    }

    /**
     * Personalized logger.
     * @param $msg
     */
    protected function log($msg) {
        if($this->debug) {
            Log::info('MailjetMailer: ' . $msg);
        }
    }

    public function getVersion() {
        return $this->version;
    }

    public function getEnvironment() {
        return $this->environment;
    }

    public function getConfig() {
        return $this->config;
    }

    public function getAccount() {
        return $this->account;
    }
}