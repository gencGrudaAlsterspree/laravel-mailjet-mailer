<?php

namespace WizeWiz\MailjetMailer\Events;

use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;
use WizeWiz\MailjetMailer\MailerResponse;

class EmailSend extends BaseEvent {

    public $Requests;
    public $MailerResponse;

    /**
     * EmailSend constructor.
     *
     * @param MailjetRequestable $Requests
     * @param MailerResponse $MailerResponse
     */
    public function __construct(MailjetRequestable $Requests, MailerResponse $MailerResponse) {
        $this->Requests = $Requests;
        $this->MailerResponse = $MailerResponse;
    }

}