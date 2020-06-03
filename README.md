# Mailjet Mailer

#### Status

v1.3.1 (3. June 2020) - _in-development_

**This documentation is incomplete, currently the documentation is being revised**

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

Publish migration and configuration files with `php artisan vendor:publish`.

Create the tables using `php artisan migrate`.

### Setup

#### Local .env

The mailjet library uses two properties in the `.env` file. We use the same
properties to define the default configuration for [MailjetMailer](https://github.com/wize-wiz/laravel-mailjet-mailer) to access
Mailjets Send API.

```bash
MAILJET_APIKEY=mailjets-api-key
MAILJET_APISECRET=mailjets-api-secret
```

If you have multiple mailjet accounts (e.g. for development), the `MAILJET_MAILER_ACCOUNT` environment variable sets the account to use. This independently of the used environment. 

```bash
MAILJET_MAILER_ACCOUNT=live
```

To prevent E-Mails being accidentally sent to customers, an email interceptor can be enabled. By default, this will intercept all emails. In the `mailjet-mailer` config, email addresses can be whitelisted and will be ignored by the interceptor.

```bash
MAILJET_MAILER_INTERCEPT=true
MAILJET_MAILER_INTERCEPT_TO=intercept@mail.local
MAILJET_MAILER_INTERCEPT_TO_NAME="Mailjet Interceptor"
```
<br />

#### Config

After sucessfull installation and publishing the configuration file using `php artisan vendor:publish`. A default configuration file will be located under `config/mailjet-mailer.php`.

Each `accounts` key represents an environment. A `default` configuration is already set with all available options:

```php
return [
	'accounts' => [
	    'default' => [
	        'key' => env('MAILJET_APIKEY'),
	        'secret' => env('MAILJET_APISECRET'),
	        // templates defined in Mailjet Passport.
	        'templates' => [],
	        // which Send API version to use, e.g. v3, v3.1. 
	        'version' => env('MAILJET_MAILER_API', 'v3.1'),
	        'sender' => [
	            // sender email
	            'email' => env('MAILJET_MAILER_FROM', env('MAIL_FROM', 'sender@example.com')),
	            // sender name
	            'name' => env('MAILJET_MAILER_FROM_NAME', env('MAIL_FROM_NAME', 'Mailjet Mailer'))
	        ]
	    ]
    ]
];
```

Each environment can have its own configuration. This can be very usefull to setup different accounts for different environments (stages), e.g. `production` and `development`/`local` environments:

```php
return [
	'accounts' => [
	    'development' => [
	        'key' => 'different-api-key',
	        'secret' => 'different-api-secret',
	        // templates defined in Mailjet Passport.
	        'templates' => [
	            // different templates
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
    ]
];
```

In the `.env` file, we can switch environments easily. 

```bash
MAILJET_MAILER_ACCOUNT=development
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
    public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequestable  {
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
public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequestable  {
	return $Request
		->notify($notifiable)
		// add user with ID 100.
		->notify(User::find(100));
}
```

Also a recpient e-mail and name can be added to the recipients list:

```php
public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequestable  {
	return $Request->recipient('admin@example.com', 'Administrator');
}
```

If the email is found in the `users` table, the user will be automatically included to the notifiable list.

### Bulk (Collection)

Bulk email uses a collection to send personalized email. Here we prepare on single request to be personilized for each user and convert it to a collection. The assign method accepts a collection of users (notifiables) to be notified, the prepared request and a callback function.

```php
public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequestable  {
	// simply get 10 users.
	$Users = User::limit(10)->get();
	// prepare the request
	$Request
        // template defined in our config/mailjet-mailer.php.
        ->template('register')
        // template variables defined at Mailjets Passport.
        ->variables([
            'text' => "A message sent with Mailjet!"
        ])
        // set subject.
        ->subject('A message sent with Mailjet!');

	return $Request
		->toCollection(false)
    	->assign($Users, $Request, function($User, $Request) {
			// personalize request
			$Request
				->variable('greeting', "Hallo {$User->name}")
				->notify($User);
	
			return $Request;
		});
	        
	}
```

### Queue

If a queue should be used, all options for a queue are available:

```php
public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequestable  {
	$connection = 'redis';
	$queue = 'mail';
	$delay = 60;
	// put request on a queue.
	return $Request->queue($connection, $queue, $delay);
}

```

### <a name="webhook"></a> Webhook API

- explain webhook client.
- all events
- url: **/api/mailjet/webhook**
- configuration in Mailjet

### <a name="backup-driver"></a> Backup Diver

- explain backup driver

If Mailjet fails to deliver the E-Mail or fails to connect with Mailjets
Send API, a backup can be configured to deliver the e-mail. 

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

### Todo
- workout readme (1/2)
- add examples (1/2)
- update composer.json for all depenencies/requirements. (0/1)