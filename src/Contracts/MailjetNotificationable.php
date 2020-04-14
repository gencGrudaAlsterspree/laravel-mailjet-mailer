<?php

namespace WizeWiz\MailjetMailer\Contracts;

use WizeWiz\MailjetMailer\Mailer;
use Illuminate\Notifications\Notification;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

interface MailjetNotificationable {
    public function processMailjet(MailjetMessageable $notifiable, Notification $Notification, MailjetRequest $Request);
    public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequest;
}