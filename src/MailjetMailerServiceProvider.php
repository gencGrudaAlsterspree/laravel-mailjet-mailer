<?php

namespace WizeWiz\MailjetMailer;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MailjetMailerServiceProvider extends ServiceProvider {

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
         Event::subscribe(Listeners\MailjetWebhookEventSubscriber::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        // mailjet-mailer migrations
        $this->loadMigrationsFrom(__DIR__.'/migrations/');
        // include routes
        $this->loadRoutesFrom(__DIR__.'/routes/webhook.php');
    }
}