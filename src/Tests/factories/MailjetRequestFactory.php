<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use WizeWiz\MailjetMailer\Collections\MailjetRequestCollection;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

$factory->define(MailjetRequest::class, function (Faker $faker) {
    return [
        'from_name' => 'MailjetMailer Test',
        'from_email' => 'api@mailjet-mailer.test',
        'recipients' => [
            [
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail
            ]
        ],
        'subject' => 'A Subject',
        'version' => 'v3.1',
        'created_at' => now()
    ];
});

$factory->define(MailjetRequestCollection::class, function () {
    return [];
});
