<?php

namespace WizeWiz\MailjetMailer;

use Illuminate\Support\ServiceProvider;

class MailjetMailerServiceProvider extends ServiceProvider {

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {}

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