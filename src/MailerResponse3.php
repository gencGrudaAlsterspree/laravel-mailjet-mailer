<?php

namespace WizeWiz\MailjetMailer;

use Mailjet\Response as MailjetResponse;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

class MailerResponse3 extends MailerResponse {

    /**
     * MailerResponse3 constructor.
     * @param MailjetResponse $Response
     */
    public function __construct(MailjetRequest $Request, MailjetResponse $Response) {
        // pass on response data and set Send API version
        parent::__construct($Request, $Response, Mailer::VERSION_3);
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
     * Inspect response data according to Send API v3
     * @param Mailer|null $Mailer
     * @return array
     */
    public function analyze() : void {
        return [];
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