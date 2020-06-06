<?php

namespace WizeWiz\MailjetMailer\Concerns;

use WizeWiz\MailjetMailer\Events\Webhook\WebhookEvent;
use WizeWiz\MailjetMailer\Models\MailjetWebhookEvent;

trait HasWebhookEvents {

    /**
     * Many relation.
     *
     * @return mixed
     */
    public function mailjet_webhook_events() {
        return $this->hasMany(MailjetWebhookEvent::class, 'mailjet_id', 'mailjet_id');
    }

    /**
     * Get the event by $event name.
     *
     * @param string $event
     * @return MailjetWebhookEvent|null
     */
    protected function getEvent(string $event) {
        return $this
            ->mailjet_webhook_events()
            ->where('event', $event)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get the event error, if any.
     *
     * @param MailjetWebhookEvent|null $Event
     * @return array|null
     */
    protected function getEventError(?MailjetWebhookEvent $Event) {
        return $Event ? [
            'error' => isset($Event->data['error']) ?
                $Event->data['error'] : null,
            'error_related_to' => isset($Event->data['error_related_to']) ?
                $Event->data['error_related_to'] : null,
            'comment' => isset($Event->data['comment']) ?
                $Event->data['comment'] : null
        ] : null;
    }


    /**
     * If sent, opened, clicked, unsubscribed or marked as spam, the email was sent.
     *
     * @return bool
     */
    public function isSent() : bool {
        $status = $this->delivery_status;
        return
            $status === WebhookEvent::EVENT_SENT ||
            $status === WebhookEvent::EVENT_OPEN ||
            $status === WebhookEvent::EVENT_CLICK ||
            $status === WebhookEvent::EVENT_UNSUB ||
            $status === WebhookEvent::EVENT_SPAM;
    }

    /**
     * If email was opened, clicked or unsubscribed, the email was (at least) opened.
     *
     * @return bool
     */
    public function isOpened() : bool {
        $status = $this->delivery_status;
        return
            $status === WebhookEvent::EVENT_OPEN ||
            $status === WebhookEvent::EVENT_CLICK ||
            $status === WebhookEvent::EVENT_UNSUB;
    }

    /**
     * If any of the CTA was clicked.
     *
     * @return bool
     */
    public function isClicked() : bool {
        return $this->delivery_status === WebhookEvent::EVENT_CLICK;
    }

    /**
     * If bounced, see https://dev.mailjet.com/email/guides/webhooks/#possible-values-for-errors
     *
     * @return bool
     */
    public function isBounced() {
        return $this->delivery_status === WebhookEvent::EVENT_BOUNCE;
    }

    /**
     * Get bounce error.
     * see https://dev.mailjet.com/email/guides/webhooks/#possible-values-for-errors
     *
     * @return array|null
     */
    public function getBounceReason() {
        if(!$this->isBounced()) {
            return null;
        }

        $Event = $this->getEvent(WebhooKEvent::EVENT_BOUNCE);
        return $Event ?
            $this->getEventError($Event) : null;
    }

    /**
     * If bounce is temporary. If 5 days straight the e-mail can't be delivered, it will
     * be marked as hard bounced.
     *
     * @return bool
     */
    public function isSoftBounce() : bool {
        if(!$this->isBounced()) {
            return false;
        }

        $Event = $this->getEvent(WebhookEvent::EVENT_BOUNCE);
        return
            $Event &&
            isset($Event->data['hard_bounce']) &&
            $Event->data['hard_bounce'] === false;
    }

    /**
     * If bounce is permanent. E-mail will never be delivered.
     *
     * @return bool
     */
    public function isHardBounce() : bool {
        if(!$this->isBounced()) {
            return false;
        }

        $Event = $this->getEvent(WebhookEvent::EVENT_BOUNCE);
        return
            $Event &&
            isset($Event->data['hard_bounce']) &&
            $Event->data['hard_bounce'] === true;
    }

    /**
     * If e-mail was blocked.
     *
     * @return bool
     */
    public function isBlocked() : bool {
        return $this->delivery_status === WebhookEvent::EVENT_BLOCKED;
    }

    /**
     * Get blocked error.
     *
     * @return array|null
     */
    public function getBlockedReason() {
        if(!$this->isBlocked()) {
            return null;
        }

        $Event = $this->getEvent(WebhookEvent::EVENT_BLOCKED);
        return $this->getEventError($Event);
    }

    /**
     * If was marked as spam.
     *
     * @return bool
     */
    public function isSpam() : bool {
        return $this->delivery_status === WebhookEvent::EVENT_SPAM;
    }

    /**
     * Source of e-mail being marked as spam.
     *
     * @return |null
     */
    public function getSpamSource() {
        if(!$this->isSpam()) {
            return null;
        }

        $Event = $this->getEvent(WebhookEvent::EVENT_SPAM);
        if($Event && isset($Event->data['source'])) {
            return $Event->data['source'];
        }
        return null;
    }

    /**
     * Unsubscribed of list.
     *
     * @return bool
     */
    public function isUnsubscribed() : bool {
        return $this->delivery_status === WebhookEvent::EVENT_UNSUB;
    }
}