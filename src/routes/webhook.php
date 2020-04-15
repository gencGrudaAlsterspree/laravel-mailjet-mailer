<?php

/**
 * Make sure to `php artisan route:clear` when routes are not accessible under "api/"
 */

Route::middleware('api')
    ->namespace('WizeWiz\MailjetMailer\Controllers')
    ->prefix('api')
    ->group(function() {
        // main: indirect call to all events.
        Route::post('mailjet/webhook', 'WebhookClient@index');
        //  direct call to each event.
        Route::post('mailjet/webhook/blocked', 'WebhookClient@onBlocked');
        Route::post('mailjet/webhook/bounce', 'WebhookClient@onBounce');
        Route::post('mailjet/webhook/click', 'WebhookClient@onClick');
        Route::post('mailjet/webhook/open', 'WebhookClient@onOpen');
        Route::post('mailjet/webhook/sent', 'WebhookClient@onSent');
        Route::post('mailjet/webhook/spam', 'WebhookClient@onSpam');
        Route::post('mailjet/webhook/unsub', 'WebhookClient@onUnsub');
});