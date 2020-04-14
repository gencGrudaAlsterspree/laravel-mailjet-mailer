<?php

namespace WizeWiz\MailjetMailer\Contracts;

interface MailjetableModel {
    public function asRecipient() : array;
    public function getEmail() : string;
    public function getName() : string;
}