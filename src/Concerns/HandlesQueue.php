<?php

namespace WizeWiz\MailjetMailer\Concerns;

use WizeWiz\MailjetMailer\Jobs\MailjetJobRequest;

trait HandlesQueue {

    /**
     * @var bool
     */
    protected $should_queue = false;

    /**
     * @var string|null
     */
    protected $queue_connection = null;

    /**
     * @var string
     */
    protected $queue_queue = null;

    /**
     * @var int|null
     */
    protected $queue_delay = null;

    /**
     * Set connection for the queue.
     * @param $connection
     * @return $this
     */
    public function queueConnection($connection) {
        $this->queue_connection = $connection;
        return $this;
    }

    /**
     * Return queue connection.
     * @return null|string
     */
    public function getQueueConnection() {
        return $this->queue_connection;
    }

    /**
     * If queue_connection was set.
     * @return bool
     */
    public function hasQueueConnection() {
        if($this->shouldQueue()) {
            return !empty($this->queue_connection);
        }
        return false;
    }

    /**
     * If queue_connection was set.
     * @return bool
     */
    public function hasQueueQueue() {
        if($this->shouldQueue()) {
            return !empty($this->queue_queue);
        }
        return false;
    }

    /**
     * If queue_connection was set.
     * @return bool
     */
    public function hasQueueDelay() {
        if($this->shouldQueue()) {
            return !empty($this->queue_delay);
        }
        return false;
    }

    /**
     * Set queue queue.
     * @param string $queue
     * @return $this
     */
    public function queueQueue($queue = 'default') {
        $this->queue_queue = $queue;
        return $this;
    }

    /**
     * Return queue queue.
     * @return string
     */
    public function getQueueQueue() {
        return $this->queue_queue;
    }

    /**
     * Set queue delay.
     * @param $delay
     * @return $this
     */
    public function queueDelay($delay) {
        $this->queue_delay = $delay;
        return $this;
    }

    /**
     * Return delay for queue.
     * @return null|integer
     */
    public function getQueueDelay() {
        return $this->queue_delay;
    }


    /**
     * Should the Mailer be queued.
     * @return bool
     */
    public function shouldQueue() : bool {
        return $this->should_queue;
    }

    /**
     * Set queue.
     * @param $queue
     * @param $connection_or_delay
     * @param $delay
     * @return $this
     */
    public function queue($queue = 'default', $connection_or_delay = null, $delay = null) {
        if (is_array($queue)) {
            $delay = isset($queue['delay']) ? $queue['delay'] : $delay;
            $queue = isset($queue['queue']) ? $queue['queue'] : $queue;
            $connection_or_delay = isset($queue['connection']) ? $queue['connection'] : null;
        }

        // if ($queue, $delay) was passed.
        if(!is_null($connection_or_delay) && !is_string($connection_or_delay)) {
             $delay = $connection_or_delay;
             $connection_or_delay = null;
        }

        // we should queue the request.
        $this->should_queue = true;
        // set queue parameters.
        $this->queueQueue($queue);
        $this->queueConnection($connection_or_delay);
        $this->queueDelay($delay);

        return $this;
    }

    /**
     * Return queue connection.
     * @return array
     */
    public function getQueue() : array {
        return [
            'connection' => $this->queue_connection,
            'queue' => $this->queue_queue,
            'delay' => $this->queue_delay
        ];
    }

    /**
     * Create a job to process this request.
     * @param array $options
     * @param MailjetJobRequest
     */
    public function makeJob(array $options = []) {
        // create job
        $Job = new MailjetJobRequest($this, $options);
        if($this->queue_connection !== null) {
            $Job->onConnection($this->queue_connection);
        }
        if($this->queue_delay !== null) {
            $Job->delay($this->queue_delay);
        }
        return $Job;
    }

}