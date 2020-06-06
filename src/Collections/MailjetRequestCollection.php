<?php

namespace WizeWiz\MailjetMailer\Collections;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mailjet\Response as MailjetLibResponse;
use WizeWiz\MailjetMailer\Concerns\HandlesQueue;
use WizeWiz\MailjetMailer\Concerns\HandlesRequestable;
use WizeWiz\MailjetMailer\Concerns\HandlesDynamicProperties;
use WizeWiz\MailjetMailer\Contracts\HasDynamicProperties;
use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;
use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;
use WizeWiz\MailjetMailer\Exceptions\InvalidNotifiableException;
use WizeWiz\MailjetMailer\Exceptions\InvalidRecipientException;
use WizeWiz\MailjetMailer\Mailer;
use WizeWiz\MailjetMailer\MailerResponse;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

class MailjetRequestCollection extends Collection implements MailjetRequestable, HasDynamicProperties {

    const STATUS_UNKNOWN = 'unknown';

    use HandlesQueue,
        HandlesDynamicProperties,
        HandlesRequestable {
            useSandbox as setSandbox;
        }

    protected $version;
    protected $sandbox = false;

    /**
     * If each request should be queued seperately.
     * @var bool
     */
    protected $queue_each = false;

    /**
     * MailjetRequestCollection constructor.
     * @param null $version
     * @param array $items
     */
    public function __construct($version = null, $items = []) {
        parent::__construct($items);
        $this->version = $version === null ? Mailer::DEFAULT_VERSION : $version;
    }

    /**
     * Get dynamic property.
     * @param $property
     * @return string|null
     */
    public function getDynamicProperty($property) {
        switch($property) {
            case 'status':
                // we have multiple requests with possible multiple status.
                $status_collect = [];
                foreach($this->items as $index => $Request) {
                    $status_collect[empty($Request->id) ? $index : $Request->id] = $Request->status;
                }
                return $status_collect;
        }
        return null;
    }

    /**
     * Check if all requests are prepared.
     */
    public function isPrepared() {
        /**
         * @var MailjetRequest $Request
         */
        foreach($this->items as $Request) {
            if($Request->isPrepared() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prepare all requests.
     * @param bool $dry
     */
    public function prepareAll($dry = false) {
        /**
         * @var MailjetRequest $Request
         */
        foreach($this->items as $Request) {
            if($Request->isPrepared() === false) {
                // pass on the queue information.
                if($this->shouldQueue() && !$Request->shouldQueue()) {
                    $Request->queue($this->getQueue());
                }
                // set request mode.
                $Request->setRequestMode($dry === false ? 'live' : 'dry');
                // prepare request before sending it.
                $Request->prepare();
            }
        }
    }

    /**
     * Build Bulk body for request
     */
    public function buildBody() {
        $body = [
            'Messages' => []
        ];

        /**
         * @var MailjetRequest $Request
         */
        foreach($this->items as $Request) {
            $request_body = $Request->buildBody();
            var_dump($request_body);
            array_push($body['Messages'], current($request_body['Messages']));
        }

        if($this->isSandboxed()) {
            $body['SandboxMode'] = true;
        }

        return $body;
    }

    /**
     * Queue each request seperately.
     * @param bool $queue_each
     * @return $this
     */
    public function queueEach($queue_each = true) {
        if($this->shouldQueue() === false) {
            $this->queue();
        }

        $this->queue_each = $queue_each;
        return $this;
    }

    /**
     * If each request should be queued seperately.
     * @return bool
     */
    public function shouldQueueEach() : bool {
        return $this->queue_each;
    }

    /**
     * Use sandbox.
     * @note overwritten from HandlesRequestable trait.
     * @param bool $sandbox
     * @return MailjetRequestCollection
     */
    public function useSandbox($sandbox = true) {
        if(!$this->isEmpty()) {
            foreach ($this->items as $Request) {
                $Request->useSandbox($sandbox);
            }
        }
        return $this->setSandbox($sandbox);
    }

    /**
     * Update requests from Response.
     * @param $error
     * @param MailerResponse $Response
     * @param MailjetLibResponse $LibResponse
     */
    public function updateFromResponse($error, MailerResponse $Response, MailjetLibResponse $LibResponse) {
        /**
         * @var MailjetRequest $Request
         */
        foreach($this->items as $Request) {
            try {
                $Request->updateFromResponse($error, $Response, $LibResponse);
            // do not fail others to be updated if one fails.
            } catch(\Exception $e) {
                var_dump($e->getMessage());
                var_dump($e->getFile() . ' @ ' . $e->getLine());
            }
        }
    }

    /**
     * Reinitialize all requests, e.g. when serialized in a queue.
     */
    public function reinitialize() : void {
        foreach($this->items as $Request) {
            $Request->reinitialize();
        }
    }

    /**
     * Create a new request.
     */
    public function newRequest() : MailjetRequest {
        return MailjetRequest::make([
            'version' => $this->version === null ? Mailer::DEFAULT_VERSION : $this->version,
            'sandbox' => $this->sandbox
        ]);
    }

    /**
     * Add only items of type MailjetRequest.
     * @param mixed $item
     * @return MailjetRequestCollection
     * @throws \Exception
     */
    public function add($item) {
        if(!$item instanceof MailjetRequest) {
            // @todo: cutom exception
            throw new \Exception('Add item should be an instance of '.MailjetRequest::class.'.');
        }
        return parent::add($item);
    }

    /**
     * Add only items of type MailjetRequest.
     * @param mixed $key
     * @param mixed $value
     * @throws \Exception
     */
    public function offsetSet($key, $value) {
        if(!$value instanceof MailjetRequest) {
            // @todo: cutom exception
            throw new \Exception('Add item should be an instance of '.MailjetRequest::class.'.');
        }
        parent::offsetSet($key, $value);
    }

    /**
     * Mass assign notifiables to a predefined Request.
     * @param $notifiables
     * @param MailjetRequest $Request
     * @param callable $callback
     * @return MailjetRequestCollection
     * @throws \Exception
     */
    public function assign($notifiables, MailjetRequest $Request, callable $callback) {
        if(!is_iterable($notifiables)) {
            $notifiables = collect([$notifiables]);
        }
        // we will overwrite all from given $Request.
        // @todo: either the RequestCollection dominates the settings of Request, or the other way around.
        $this->useSandbox($Request->isSandboxed());
        if($Request->shouldQueue()) {
            $this->queue($Request->getQueue());
        }
        $this->version = $Request->getVersion();
        // clone a new request from given $Request for each $notifiable.
        foreach($notifiables as $key => $notifiable) {
            // if it is not a MailjetMessageable object.
            if(!$notifiable instanceof MailjetMessageable) {
                if(!is_array($notifiable)) {
                    throw new InvalidNotifiableException();
                }

                if(!isset($notifiable['email']) || !isset($notifiable['name'])) {
                    throw new InvalidRecipientException();
                }
            }

            $NotifiableRequest = $callback($notifiable, clone $Request, $key);
            if(!$NotifiableRequest instanceof MailjetRequest) {
                // @todo: cutom exception
                throw new \Exception('callback in '.static::class.'::assign should return an instance of '.MailjetRequest::class);
            }
            // add notifiable if no recipients were added.
            if($NotifiableRequest->hasRecipients() === false) {
                $NotifiableRequest->notify($notifiable);
            }

            $this->add($NotifiableRequest);
        }
        return $this;
    }

    /**
     * Refresh requests.
     */
    public function refresh() : void {
        foreach($this->items as $Request) {
            $Request->refresh();
        }
    }

    /**
     * Gather and return all messages from all requests.
     * @return Collection
     */
    public function mailjet_messages() : Collection {
        $all_messages = collect([]);
        foreach($this->items as $Request) {
            $messages = $Request->mailjet_messages;
            if(!empty($messages)) {
                $all_messages = $all_messages->merge($messages);
            }
        }
        return $all_messages;
    }

}