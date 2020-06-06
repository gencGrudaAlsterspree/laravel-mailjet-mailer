<?php

namespace WizeWiz\MailjetMailer\Contracts;

interface MailjetMessageable {
    public function mailjetableRecipient() : array;
    public function mailjetableEmail() : string;
    public function mailjetableName() : string;
    public function getMailjetableEmailAttribute() : string;
    public function getMailjetableNameAttribute() : string;
    public function mailjet_messages();
    public function mailjet_requests();
}