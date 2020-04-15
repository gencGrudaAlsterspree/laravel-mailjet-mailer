<?php

namespace WizeWiz\MailjetMailer\Concerns;

trait HandlesMailjetableModel {

    /**
     * Return array with ['email' => '', 'name' => '']
     * @return array
     */
    public function mailjetableRecipient() : array {
        return [
            'email' => $this->mailjetableEmail(),
            'name' => $this->mailjetableName()
        ];
    }

    /**
     * Default implementation of Email.
     * @return string
     */
    public function mailjetableEmail() : string {
        return $this->email;
    }

    /**
     * Default implementation of Name.
     * @return string
     */
    public function mailjetableName() : string {
        return $this->name;
    }

}