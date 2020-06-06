<?php

/**
 * Make sure to run `php artisan route:clear` when routes are not accessible under "api/"
 */

Route::middleware(config('mailjet-mailer.webhook.middleware'))
    ->namespace('WizeWiz\MailjetMailer\Controllers')
    ->prefix(config('mailjet-mailer.webhook.prefix'))
    ->group(function() {
        $route = config('mailjet-mailer.webhook.endpoint');
        // main: indirect call to all events.
        Route::post($route, 'WebhookClient@index');
        //  direct call to each event.
        Route::post($route.'/blocked', 'WebhookClient@onBlocked');
        Route::post($route.'/bounce', 'WebhookClient@onBounce');
        Route::post($route.'/click', 'WebhookClient@onClick');
        Route::post($route.'/open', 'WebhookClient@onOpen');
        Route::post($route.'/sent', 'WebhookClient@onSent');
        Route::post($route.'/spam', 'WebhookClient@onSpam');
        Route::post($route.'/unsub', 'WebhookClient@onUnsub');
});