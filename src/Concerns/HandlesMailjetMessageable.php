<?php

namespace WizeWiz\MailjetMailer\Concerns;

use WizeWiz\MailjetMailer\Models\MailjetMessage;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

trait HandlesMailjetMessageable {

    /**
     * Morphable
     */
    public function mailjet_messages() {
        // latest message first.
        return $this->morphMany(MailjetMessage::class, 'mailjet_messageble')->orderBy('created_at', 'desc');
    }

    /**
     * Morphable
     */
    public function mailjet_requests() {
        // latest request first.
        return $this->morphToMany(MailjetRequest::class, 'mailjet_notifiable', 'mailjet_notifiables')->orderBy('created_at', 'desc');
    }

    /**
     * Attribute to retrieve the recipient email.
     *
     * @return string
     */
    public function getMailjetableEmailAttribute() : string {
        return 'email';
    }

    /**
     * Attribute to retrieve the recipient name.
     *
     * @return string
     */
    public function getMailjetableNameAttribute() : string {
        return 'name';
    }

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
        return $this->{$this->getMailjetableEmailAttribute()};
    }

    /**
     * Default implementation of Name.
     * @return string
     */
    public function mailjetableName() : string {
        return $this->{$this->getMailjetableNameAttribute()};
    }

}