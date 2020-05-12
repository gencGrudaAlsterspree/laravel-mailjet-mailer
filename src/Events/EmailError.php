<?php

namespace WizeWiz\MailjetMailer\Events;

use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;
use WizeWiz\MailjetMailer\MailerResponse;

class EmailError extends BaseEvent {

    public $Requests;
    public $MailerResponse;

    /**
     * EmailError constructor.
     *
     * @param MailjetRequestable $Requests
     * @param MailerResponse $MailerResponse
     */
    public function __construct(MailjetRequestable $Requests, MailerResponse $MailerResponse) {
        $this->Requests = $Requests;
        $this->MailerResponse = $MailerResponse;
    }

}