<?php

namespace WizeWiz\MailjetMailer\Contracts;

interface HasDynamicProperties {

    public function __get($property);
    public function getDynamicProperty($property);

}