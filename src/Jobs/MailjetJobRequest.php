<?php

namespace WizeWiz\MailjetMailer\Jobs;

use WizeWiz\MailjetMailer\Mailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

/**
 * Class MailjetJobRequest for dispatch Mailjet/Mailer as a job (queue).
 * @package WizeWiz\MailjetMailer\Jobs
 */
class MailjetJobRequest implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var MailjetRequest
     */
    public $Request;

    /**
     * @var array
     */
    public $options;

    /**
     * Create a new job instance.
     * @param MailjetRequest $Request
     * @param array $options
     * @return void
     */
    public function __construct(MailjetRequest $Request, array $options = []) {
        if($Request->shouldQueue()) {
            $this->setQueue($Request);
        }
        $this->Request = $Request;
        $this->options = $options;
    }

    /**
     * Configure queue if $Request should be queued.
     * @param MailjetRequest $Request
     */
    private function setQueue(MailjetRequest $Request) {
        if($Request->hasQueueConnection()) {
            $this->onConnection($Request->getQueueConnection());
        }
        if($Request->hasQueueQueue()) {
            $this->onQueue($Request->getQueueQueue());
        }
        if($Request->hasQueueDelay()) {
            $this->delay($Request->getQueueDelay());
        }
    }

    /**
     * Execute the job.
     * @return void
     * @throws \Exception
     */
    public function handle() {
        try {
            // reinitialize model when job runs
            $this->Request->reinitialize();
            // process request.
            (new Mailer())->process($this->Request, $this->options);
        } catch(\Exception $e) {
            // @todo: handle Exception, requeue, send to backup, etc.
            Log::info('MailjetJobRequest::catch');
            Log::info($e->getMessage());
            Log::info($e->getFile() . ' : ' . $e->getLine());
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     * @return array
     */
    public function tags() {
        return ['mailjet-mailer', 'mail', 'request:' . $this->Request->id];
    }
}
