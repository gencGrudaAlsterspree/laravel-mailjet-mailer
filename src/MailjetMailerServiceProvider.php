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
        Event::subscribe(Listeners\MailjetWebhookEventSubscriber::class);
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

        // mailjet-mailer migrations
        $this->loadMigrationsFrom(__DIR__.'/migrations/');
        // include routes
        $this->loadRoutesFrom(__DIR__.'/routes/webhook.php');
    }
}