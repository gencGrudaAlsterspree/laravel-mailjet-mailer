<?php

namespace WizeWiz\MailjetMailer\Notifications;

use WizeWiz\MailjetMailer\Channels\MailjetChannel;
use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;
use WizeWiz\MailjetMailer\Contracts\MailjetNotificationable;
use Illuminate\Bus\Queueable;
use WizeWiz\EnhancedNotifications\Notifications\Notification;
use Illuminate\Notifications\Notification as LaravelNotification;
use WizeWiz\MailjetMailer\Mailer;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

abstract class MailjetNotification extends Notification implements MailjetNotificationable {
    use Queueable;

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
    public function via() {
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
        $Request = $this->toMailjet($notifiable, $Request);
        // if notfiable or recipient not set, add notfiable
        if(empty($Request->getRecipients()) && empty($Request->getNotifiables())) {
            $Request->notify($notifiable);
        }
        // @todo: check if recipients or notifiables are empty.
        // prepare Mailer to send if not already send.
        if($Request->isSent() === false) {
            // set predefined subject
            // @todo: $this->subject should work as well.
            $Request->subject($notification->getSubject());
            // set predifined template
            // @todo: $this->template_name should work as well
            $Request->template($notification->getTemplateName());
            // set variables with variable defaults
            // @todo: $this->default_variables should work as well.
            $Request->variables(array_merge($notification->getDefaultVariables(), $Request->variables));
            // send request.
            Mailer::send($Request);
        }
    }

    /**
     * Send Mailjet E-Mail. Needs to be implemented by MailjetChannel.
     * @param MailjetMessageable $notifiable
     * @param MailjetRequest $Request
     * @return Mailer
     */
    abstract public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequest;
}
