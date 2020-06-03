<?php

namespace WizeWiz\MailjetMailer\Notifications;

use WizeWiz\MailjetMailer\Channels\MailjetChannel;
use WizeWiz\MailjetMailer\Collections\MailjetRequestCollection;
use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;
use WizeWiz\MailjetMailer\Contracts\MailjetNotificationable;
use Illuminate\Notifications\Notification as LaravelNotification;
use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;
use WizeWiz\MailjetMailer\Mailer;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

abstract class MailjetNotification extends LaravelNotification implements MailjetNotificationable {

    /**
     * E-Mail subject.
     * @var string
     */
    protected $subject = '';

    /**
     * Template name to be used. Template names are defined in the config.mailjet.*
     * @var string
     */
    protected $template_name;

    /**
     * Variables required by the template.
     * @var array
     */
    protected $variables;

    /**
     * Return sandboxed.
     * @return bool
     */
    public function isSandboxed() : bool {
        return $this->sandbox;
    }

    /**
     * Return subject
     * @return string
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * Return template name. Template names are defined in the config.mailjet.*
     * @return string
     */
    public function getTemplateName() {
        return $this->template_name;
    }

    /**
     * Get defaul variables.
     * @return array
     */
    public function getDefaultVariables() : array {
        return $this->variables;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable) {
        return [
            // make sure notification gets created before Mailjet channel kicks in. Mailjet/Mailer sends the
            // notification::id as the CustomID property for reference.
            'database',
            // MailjetChannel publishes the toMailjet method.
            MailjetChannel::class
        ];
    }

    /**
     * Process Mailjet.
     * @param MailjetMessageable $notifiable
     * @param LaravelNotification $notification
     * @param Mailer $Mailer
     * @throws \Exception
     */
    public function processMailjet(MailjetMessageable $notifiable, LaravelNotification $notification, MailjetRequest $Request) {
        // call implement toMailjet channel method to apply custom content.
        $Requestable = $this->toMailjet($notifiable, $Request);

        // verify notifiable was added.
        if($Requestable instanceof MailjetRequest) {
            // if notifiable or recipient not set, add notfiable
            if (empty($Requestable->getRecipients()) && empty($Requestable->getNotifiables())) {
                $Requestable->notify($notifiable);
            }
        }

        // prepare Mailer to send if not already send.
        if($Requestable->isSent() === false) {

            // @todo: think of a way to automatically add template settings (and or subject) from MailjetNotification when
            //          $Requestable is a MailjetRequestCollection.
            // @note: quick solution.
            if(!$Requestable instanceof MailjetRequestCollection) {
                $Requestable = $Requestable->toCollection();
            }

            // if($Requestable instanceof MailjetRequest) {
            foreach($Requestable as $Request) {
                // set predefined subject
                if(empty($Request->subject) && !empty( ($subject = $notification->getSubject()) )) {
                    $Request->subject($subject);
                }
                // set predifined template
                if(empty($Request->template_name) && !empty( ($template_name = $notification->getTemplateName()) )) {
                    $Request->template($template_name);
                }
            }
            // send request.
            (new Mailer())->send($Requestable);
        }
    }

    /**
     * Send Mailjet E-Mail. Needs to be implemented by MailjetChannel.
     * @param MailjetMessageable $notifiable
     * @param MailjetRequest $Request
     * @return Mailer
     */
    abstract public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequestable;
}
