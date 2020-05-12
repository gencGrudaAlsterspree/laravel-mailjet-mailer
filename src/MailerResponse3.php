<?php

namespace WizeWiz\MailjetMailer;

use Mailjet\Response as MailjetResponse;
use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;

class MailerResponse3 extends MailerResponse {

    /**
     * MailerResponse3 constructor.
     * @param MailjetResponse $Response
     */
    public function __construct(MailjetRequestable $Requests, MailjetResponse $Response) {
        // pass on response data and set Send API version
        parent::__construct($Requests, $Response, Mailer::VERSION_3);
    }

    /**
     * Set response properies.
     */
    protected function setProperties(): void {

    }

    /**
     * Is response structure valid
     * @return bool
     */
    public function isValid() : bool {
        // TODO: Implement isValid() method.
        return true;
    }

    /**
     * Set errors.
     * @param Mailer|null $Mailer
     */
    protected function setErrors() {}

    /**
     * Set success and create MailerMessage models.
     */
    protected function setSuccess() {}

}