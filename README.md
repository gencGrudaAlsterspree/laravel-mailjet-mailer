# Mailjet Mailer

#### Status

v1.2.1 (2020-17-04) - in-development

### About this package

**This package is "work in progress". Nothing in this repository is production ready!**

This package integrates an easier way to work with Mailjets
Send API v3 and v3.1. This package uses the [mailjet/mailjet-apiv3-php](https://github.com/mailjet/mailjet-apiv3-php)
library API.

It does **not** try to integrate a Mailjet driver for Swift Mailer
or make use of Mailjets SMTP Relay. It is completely based on Mailjets
Send API.

### Installation

Install with composer with `composer require wize-wiz/laravel-mailjet-mailer`.

### Setup

#### Local .env

The mailjet library uses two properties in the `.env` file. We use the same
properties to define the default configuration for [MailjetMailer](https://github.com/wize-wiz/laravel-mailjet-mailer) to access
Mailjets Send API.

```bash
MAILJET_APIKEY=mailjets-api-key
MAILJET_APISECRET=mailjets-api-secret
```

A third property can be added to select a backup option:

```bash
MAILJET_MAILER_BACKUP=smtp
```

See [Backup driver](#backup-driver) for more information.

#### Config

After sucessfull installation, the configuration file will be located under
`config/mailjet-mailer.php`.

Each configuration key represents an environment. A `default` configuration
is already set with all available options:

```php
return [
    'default' => [
        'key' => env('MAILJET_APIKEY'),
        'secret' => env('MAILJET_APISECRET'),
        // templates defined in Mailjet Passport.
        'templates' => [
            // template alias for mailjet mailer.
            'register-notification' => [
                // template ID defined by Mailjet Passport.
                'id' => '1000000',
                // set to true to use Mailjets template engine.
                'language' => true,
                // any predefined variables
                'variables' => []
            ]
        ],
        // which Send API version to use, e.g. v3, v3.1. 
        'version' => env('MAILJET_MAILER_API', 'v3.1'),
        'sender' => [
            // sender email
            'email' => env('MAILJET_MAILER_FROM', env('MAIL_FROM', 'sender@example.com')),
            // sender name
            'name' => env('MAILJET_MAILER_FROM_NAME', env('MAIL_FROM_NAME', 'Mailjet Mailer'))
        ]
    ]
];
```

Each environment can have its own configuration. This can be very usefull
to setup different accounts for different environments (stages), e.g. `production`
and `development`/`local` environments:

```php
return [
    'development' => [
        'key' => 'different-api-key',
        'secret' => 'different-api-secret',
        // templates defined in Mailjet Passport.
        'templates' => [
            // different template id
            'register-notification' => [
                'id' => '1005000',
                'language' => false
            ]
        ],
        // which Send API version to use, e.g. v3, v3.1. 
        'version' => env('MAILJET_MAILER_API', 'v3.1'),
        'sender' => [
            // sender email
            'email' => 'development@example.com',
            // sender name
            'name' => 'Development Server'
        ]
    ]
];
```

Environments can also be aliased. Lets say we have two Mailjet accounts.
One for develoment (called test) and one for production (called live):

```php
return [
    // settings for live production.
    'live' => [
        'key' => 'live-api-key',
        'secret' => 'live-api-secret',
        // ...
    ],
    // settings for development purposes.
    'test' => [
        'key' => 'test-api-key',
        'secret' => 'test-api-secret',
        // ...
    ],       
    // production will use 'live' settings.
    'production' => 'live',
    // development will use 'test' settings.
    'development' => 'test',
    // local will use whatever development is using.
    'local' => 'development'
];
```

These alias values can also come from the `.env` file, e.g.:

```bash
MAILJET_MAILER_ENV=live
```

```php
return [
    'live' => [
        // ...
    ],
    'test' => [
        // ...
    ],       
    'production' => env('MAILJET_MAILER_ENV', 'live'),
    'development' => env('MAILJET_MAILER_ENV', 'test'),
    'local' => env('MAILJET_MAILER_ENV', 'test'),
];
```

### Usage

This package uses Laravels Notification system to send E-Mails via a predefined abstract Notification class called `MailjetNotification`.

First off, the notifiable instance, in most cases the `User` model, should implement the following [MailjetableModel](src/Contracts/MailjetableModel.php) contract and optionally the [HandlesMailjetableModel](src/Concerns/HandlesMailjetableModel.php) concern.

The [MailjetableModel](src/Contracts/MailjetableModel.php) will implement 3 methods, `mailjetRecipient`, `mailjetEmail` and `mailjetName`. By default the [HandlesMailjetableModel](src/Concerns/HandlesMailjetableModel.php) will assume the Model has an `email` and `name` attribute. If this is not the case, both `mailjetableEmail()` and `mailjetableName()` methods can be overwritten:

```php
namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use WizeWiz\MailjetMailer\Concerns\HandlesMailjetableModel;
use WizeWiz\MailjetMailer\Contracts\MailjetableModel;

class User extends Authenticatable implements MailjetableModel {
    
    use HandlesMailjetableModel;

    /**
     * Return custom email attribute.
     *  
     * @return string
     */
    public function mailjetableEmail() : string {
        return $this->custom_email_attribute;
    }

    /**
     * Return custom name attribute.
     *  
     * @return string
     */
    public function mailjetableName() : string {
        return $this->first_name . ' ' . $this->last_name;        
    }
}
```

Create a new notification and register the `MailjetChannel` in the `via`
method. Create an additional `toMailjet` method to fill the `MailjetRequest`.

```php
namespace App\Notifications;

use WizeWiz\MailjetMailer\Channels\MailjetChannel;
use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;
use WizeWiz\MailjetMailer\Models\MailjetRequest;

class RegisterNotification extends Notification {
    
    public function via() {
        return [
            'database',
            MailjetChannel::class
        ];       
    }    
    
    /**
     * Send an E-Mail via Mailjets Send API. 
     * 
     * @param MailjetMessageable $notifiable
     * @param MailjetRequest $Request
     * @return MailjetRequest
     */
    public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequest  {
        return $Request
            // template defined in our config/mailjet-mailer.php.
            ->template('register')
            // template variables defined at Mailjets Passport.
            ->variables([
                'greetings' => "Hello there {$notifiable->name}!"
            ])
            // set subject.
            ->subject('A message sent with Mailjet!')
            // set user to notify.
            ->notify($notifiable);
    }
    
    /**
     * Save to database
     * 
     * @return array
     */
    public function toArray() {
		return [
			// .. any custom data.
		];    	
    }
}
```

The `MailjetRequest` class has several methods to add a notifiable:

```php
public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequest  {
	return $Request
		->notify($notifiable)
		// add user with ID 100.
		->notify(User::find(100));
}
```

Also a recpient e-mail and name can be added to the recipients list:

```php
public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequest  {
	return $Request->recipient('admin@example.com', 'Administrator');
}
```

If the email is found in the `users` table, the user will be automatically included in the notifiable list.

### Queue

If a queue should be used, all options for a queue are available:

```php
public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequest  {
	$connection = 'redis';
	$queue = 'mail';
	$delay = 60;
	// put request on a queue.
	return $Request->queue($connection, $queue, $delay);
}

```

### Webhook

- explain webhook client.
- all events
- url: **/api/mailjet/webhook**
- configuration in Mailjet

### <a name="backup-driver"></a> Backup

- explain backup driver

If Mailjet fails to deliver the E-Mail or fails to connect with Mailjets
Send API, a backup can be configured to deliver the e-mail. 

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

### Todo
- workout readme (1/2)
- add examples (1/2)
- update composer.json for all depenencies/requirements. (0/1)
- create a stable version 1.0. (0/1)