<?php

namespace WizeWiz\MailjetMailer\Events;

use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;

class UnsavedUserNotice extends BaseEvent {

    public $Notifiable;

    public function __construct(MailjetMessageable $Notifiable) {
        $this->Notifiable = $Notifiable;
    }

}