<?php

namespace WizeWiz\MailjetMailer\Concerns;

use WizeWiz\MailjetMailer\Models\MailjetMessage;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

trait HasMailjetMessages {

    /**
     * Morphable
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function mailjet_messages() {
        // latest message first.
        return $this->morphMany(MailjetMessage::class, 'mailjet_messageble')->orderBy('created_at', 'desc');
    }

    public function mailjet_requests() {
        // latest request first.
        return $this->morphToMany(MailjetRequest::class, 'mailjet_notifiable', 'mailjet_notifiables')->orderBy('created_at', 'desc');
    }
}