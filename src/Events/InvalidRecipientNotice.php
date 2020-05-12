<?php

namespace WizeWiz\MailjetMailer\Events;

use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;

class InvalidRecipientNotice extends BaseEvent {

    /**
     * @var MailjetMessageable
     */
    public $Notifiable;

    public function __construct($Notifiable) {
        $this->Notifiable = $Notifiable;
    }

}