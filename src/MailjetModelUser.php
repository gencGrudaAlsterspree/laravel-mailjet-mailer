<?php

namespace WizeWiz\MailjetMailer;

/**
 * Class MailjetModelUser model handler for Mailjet notifiables.
 * @package App\Library\Mailjet
 */
class MailjetModelUser extends MailjetModelHandler {

    /**
     * Get recipient E-Mail.
     * @return mixed
     */
    public function getEmail() : string {
        return $this->notifiable->email;
    }

    /**
     * Get recipient name.
     * @return mixed
     */
    public function getName() : string {
        return $this->notifiable->name;
    }

}