<?php

namespace WizeWiz\MailjetMailer;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use WizeWiz\MailjetMailer\Commands\ClearCacheCommand;

class MailjetMailerServiceProvider extends ServiceProvider {

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        Event::subscribe(Listeners\MailjetMailerEventSubscriber::class);
        // load webhook listener if enabled.
        if(config('mailjet-mailer.webhook.enabled')) {
            // @todo: can be removed anyway.
            Event::subscribe(Listeners\MailjetWebhookEventSubscriber::class);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearCacheCommand::class
            ]);
        }
        // mailjet-mailer migrations.
        $this->loadMigrationsFrom(__DIR__.'/migrations/');
        // load webhook routes if enabled.
        if(config('mailjet-mailer.webhook.enabled')) {
            // include routes
            $this->loadRoutesFrom(__DIR__ . '/routes/webhook.php');
        }
    }
}