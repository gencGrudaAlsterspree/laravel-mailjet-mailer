<?php

namespace WizeWiz\MailjetMailer\Channels;

use WizeWiz\MailjetMailer\Mailer;
use Illuminate\Notifications\Notification;

class MailjetChannel {
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification) {
        $notification->processMailjet($notifiable, $notification, Mailer::newRequest());
    }

}