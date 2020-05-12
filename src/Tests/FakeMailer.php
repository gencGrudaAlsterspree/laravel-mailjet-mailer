<?php

namespace WizeWiz\MailjetMailer\Tests;

use Mailjet\Client as MailjetLibClient;
use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;
use WizeWiz\MailjetMailer\Mailer;

class FakeMailer extends Mailer {

    /**
     * Create a mail client.
     */
    protected function createMailClient($key, $secret, $call, $options) {
        // build Mailjet/Client
        return new Mailjet\FakeClient($key, $secret, $call, $options);
    }
}