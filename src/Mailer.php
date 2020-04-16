<?php

namespace WizeWiz\MailjetMailer;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Mailjet\Resources;
use Mailjet\Client as MailjetLibClient;
use Mailjet\Response as MailjetLibResponse;
use App\Contracts\Notifiable;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

/**
 * Mailjet wrapper for easy mangement of the Send API
 *
 * @todo: send messages with CustomID property.
 *
 * Class Mailer
 * @package App\Library
 */
class Mailer {

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
    const CONFIG = 'mailjet-mailer';

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
    protected $key;
    protected $secret;
    protected $version;

    /**
     * Mailer constructor.
     */
    public function __construct() {
        // set environment and prepare configuration
         $this->environmentalize(App::environment());
    }

    /**
     * If Mailer is initialized.
     * @return mixed
     */
    public function isInitialized() {
        return $this->initialized;
    }

    /**
     * Create a new request.
     */
    public static function newRequest($version = null) {
        return MailjetRequest::make([
            'version' => $version === null ? static::DEFAULT_VERSION : $version,
            'sandbox' => false
        ]);
    }

    /**
     * @param $option
     * @return null
     */
    public function getConfigOption($option) {
        if($this->initialized === false) {
            $this->initialize();
        }

        return isset($this->config[$option]) ? $this->config[$option] : null;
    }

    /**
     * Sets the environment and prepares the config in config/mailjet according to the environment.
     * @param $environment
     * @return mixed
     */
    private function environmentalize($environment) {
        // set environment
        $this->environment = $environment;
        // get configuration
        $config = $this->configure($environment);
        // initialize from config
        $this->initialize($config);
    }

    /**
     * @param $environment
     * @return mixed
     */
    protected function configure($environment) {
        // get config.
        $config = config(static::CONFIG);
        // load config.
        if(isset($config[$environment])) {
            $config = $config[$environment];
        }
        else {
            if(!isset($config['default'])) {
                // @todo: create custom exception.
                throw new Exception('mailjet.php required default config.');
            }
            $config = $config['default'];
        }
        // if config is an alias.
        if(is_string($config)) {
            if(config(static::CONFIG.".{$config}") === null) {
                // @todo: create custom exception.
                throw new Exception("config to alias {$config} does not exist.");
            }
            // re-run configure again with given alias.
            return $this->configure($config);
        }
        // save config
        return $this->config = $config;
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
        // validate version
        if(!in_array($options['version'], static::VERSIONS)) {
            // @todo: custom exception.
            throw new \Exception("unsupported version supplied: {$options['version']}");
        }

        // @debug
        if($this->debug) {
            $this->log("version: {$options['version']}");
            $this->log("auth: {$this->key} : {$this->secret}");
        }
        // build Mailjet/Client
        return new MailjetLibClient($this->key, $this->secret, $call, $options);
    }

    /**
     * Send transactional E-Mail.
     * @param array $options
     * @return bool|
     * @throws \Exception
     */
    public static function send(MailjetRequest $Request, array $options = [], $dry = false) {
        // increase tries for given Request.
        $Request->tried();
        // merge options
        $options = array_merge(['version' => $Request->version], $options);
        // set mode
        if($dry === false) {
            // switch mode to live
            $Request->mode('live');
        }
        else {
            // set mode to dry.
            $Request->mode('dry');
        }
        // prepare request before sending it.
        if($Request->isPrepared() === false) {
            $Request->prepare();
        }
        // if Request should be queued.
        if($Request->shouldQueue()) {
            return static::dispatch($Request, $options);
        }
        // process request
        return (new static())->process($Request, $options);
    }

    /**
     * Send dry request.
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public static function sendDry(MailjetRequest $Request, array $options = []) {
        // send message in dry mode.
        return static::send($Request, $options, true);
    }

    /**
     * Process the Request.
     * @param MailjetLibClient $MailClient
     * @param array $body
     * @return bool|mixed
     * @throws \Exception
     */
    public function process(MailjetRequest $Request, array $options) {
        // let request build the body.
        $LibResponse = null;
        // client validations $options.
        $MailClient = $this->buildClient($options);
        // get request mode
        switch($Request->getMode()) {
            case 'live':
                try {
                    $LibResponse = $MailClient->post(Resources::$Email, ['body' => $Request->buildBody()]);
                    return $this->handleResponse($Request, $LibResponse);
                } catch(\Exception $e) {
                    return $this->handleInternalError($e);
                }
            break;
            default:
            case 'dry':
                try {
                    // version 3.1 supports sandbox
                    if ($Request->getVersion() === static::VERSION_31 && $Request->isSandboxed() === true) {
                        // sandbox the request
                        $Request->useSandbox();
                        $LibResponse = $MailClient->post(Resources::$Email, ['body' => $Request->buildBody()]);
                        return $this->handleResponse($Request, $LibResponse);
                    }

                    $this->log("request: {$Request->id}");
                    $this->log("mode: {$Request->getMode()}");
                    $this->log('sandbox: ' . ($Request->isSandboxed() ? 'true' : 'false'));
                    $this->log(json_encode($Request->toArray()));
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
    protected function handleResponse(MailjetRequest $Request, MailjetLibResponse $LibResponse) {
        // return MailerResponse according to given version.
        switch($Request->getVersion()) {
            case static::VERSION_3:
                $MailerResponse = new MailerResponse3($Request, $LibResponse);
                break;
            // default v3.1
            default:
            case static::VERSION_31:
                $MailerResponse = new MailerResponse31($Request, $LibResponse);
                break;
        }
        // false if success is true, true if success was false.
        $error = !$MailerResponse->success();
        // update request with response.
        $Request->updateFromResponse($error, $MailerResponse, $LibResponse);
        // analyze data
        // @todo: do we still need analyze?
        // $MailerResponse->analyze($this);
        // return response
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
     * @param MailjetRequest $Request
     * @param $options
     * @return null|\Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function dispatch(MailjetRequest $Request, array $options = []) {
        // add to queue if specified
        if($Request->shouldQueue() === false) {
            throw new \Exception('Given Request is not marked to be queued.');
        }
        // pass mailer options to job
        $Job = $Request->makeJob($options);
        // dipatch job
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

}