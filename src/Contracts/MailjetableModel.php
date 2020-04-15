<?php

namespace WizeWiz\MailjetMailer\Contracts;

interface MailjetableModel {
    public function mailjetableRecipient() : array;
    public function mailjetableEmail() : string;
    public function mailjetableName() : string;
}