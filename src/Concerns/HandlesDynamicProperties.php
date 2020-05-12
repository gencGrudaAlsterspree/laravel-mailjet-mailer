<?php

namespace WizeWiz\MailjetMailer\Concerns;

trait HandlesDynamicProperties {

    public function __get($property) {
        return property_exists($this, $property) ?
            $this->{$property} :
            $this->getDynamicProperty($property);
    }

}