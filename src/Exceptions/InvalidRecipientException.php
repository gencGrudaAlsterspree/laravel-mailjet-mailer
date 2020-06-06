<?php

namespace WizeWiz\MailjetMailer\Exceptions;

use Throwable;

class InvalidRecipientException extends BaseException {

    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct('Invalid recipient supplied, missing key `email` or `name`.', $code, $previous);
    }

}