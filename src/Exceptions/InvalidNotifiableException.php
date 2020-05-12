<?php

namespace WizeWiz\MailjetMailer\Exceptions;

use Throwable;
use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;
use WizeWiz\MailjetMailer\Events\InvalidRecipientNotice;

class InvalidNotifiableException extends BaseException {

    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct('Invalid notifiable supplied. Notifiable should implement the' . MailjetMessageable::class .'.', $code, $previous);
    }

}