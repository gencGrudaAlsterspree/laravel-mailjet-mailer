<?php

namespace WizeWiz\MailjetMailer;

use App\Contracts\Notifiable;

abstract class MailjetModelHandler implements Contracts\MailjetableModel {

    protected $notifiable;

    public function __construct(Notifiable $notifiable) {
        $this->notifiable = $notifiable;
    }

    /**
     * Return array with ['email' => '', 'name' => '']
     * @return array
     */
    public function asRecipient() : array {
        return [
            'email' => $this->getEmail(),
            'name' => $this->getName()
        ];
    }

    public abstract function getEmail() : string;
    public abstract function getName() : string;

}