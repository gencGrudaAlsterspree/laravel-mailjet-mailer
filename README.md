
# Mailjet Mailer

### Status

v1.3.2 (6. June 2020) -  _in-development_

**This documentation is currently being revised**

### About this package

**This package is “work in progress”.**

This package integrates an easier way to work with Mailjet's Send API v3 and v3.1. This package uses the  [mailjet/mailjet-apiv3-php](https://github.com/mailjet/mailjet-apiv3-php) library API.

> Currently I'm looking for an alternative library instead of the _"official"_ [mailjet/mailjet-apiv3-php](https://github.com/mailjet/mailjet-apiv3-php). This library makes it hard to test and some events, e.g. timeouts are not caught properly.

This package does  **not**  try to integrate a Mailjet driver for Swift Mailer or make use of Mailjet's SMTP Relay. It is completely based on Mailjet's Send API. If you wish to use the `SMTP` driver, consider using [mailjet/mailjet-apiv3-php](https://github.com/mailjet/mailjet-apiv3-php) instead.

## Installation

 - Install with composer with  `composer require wize-wiz/laravel-mailjet-mailer`.  
 - Publish migration and configuration files with  `php artisan vendor:publish`.
 - Create the tables using  `php artisan migrate`.

## Setup

### Environment variables

#### Mailjet api access

The Mailjet library uses two environment variables in the `.env` file to store the API access keys. This packages uses the same variables to define the default `APIKEY` and `APISECRET` for [MailjetMailer](https://github.com/wize-wiz/laravel-mailjet-mailer)  to access Mailjet's Send API.

```bash
MAILJET_APIKEY=mailjets-api-key
MAILJET_APISECRET=mailjets-api-secret
```

If you do no wish to use the default environment variables, just change the keys in the [configuration file](#config-api-access).

#### Mailjet account settings

If you have multiple Mailjet accounts (e.g. for development), the  `MAILJET_MAILER_ACCOUNT` environment variable determines the account to be used. This is independent of the current environment (e.g. local, development or production).

> Mailjet seems to have a very strict policy considering emails being blocked as spam. I strongly suggest to use a free test account at Mailjet instead of using the actual (payed) live account for development purposes. 
>When emails don't get through Mailjet's internal spam detection mechanism and your account gets charged with a rate limit due to this behaviour, you have to contact support several times in order to remove the rate limit (10 emails per hour) from the account.

```bash
MAILJET_MAILER_ACCOUNT=production
```

#### Send API version

Mailjet currently has two active versions of the Send API,  [v3](https://dev.mailjet.com/email/guides/send-api-V3/)  and  [v3.1](https://dev.mailjet.com/email/guides/send-api-v31/). The latest [v3.1](https://dev.mailjet.com/email/guides/send-api-v31/) is used by default. However, you can change this by setting the following environment variable to either  `v3`  or  `v3.1`.

> `v3` has not yet been implemented in this package, see milestone [v1.5](https://github.com/wize-wiz/laravel-mailjet-mailer/milestone/2)

```bash
MAILJET_MAILER_API=v3
```

#### Email interceptor

To prevent e-mails being accidentally sent to customers, an email interceptor can be enabled. The interceptor will intercept all emails. for those emails who should not be intercepted can be put into a whitelist in the  `config/mailjet-mailer.php`configuration. See the  [e-mail interceptor section](#e-mail-interceptor)  for more details.

> Please note that the `BCC` and `CC` addresses are not yet cleared by the interceptor. This is planned to be added in the release v1.4 of this package.

```bash
MAILJET_MAILER_INTERCEPT=true
MAILJET_MAILER_INTERCEPT_TO=intercept@mail.local
MAILJET_MAILER_INTERCEPT_TO_NAME="Mailjet Interceptor"
```

<br />

## Config

After successful installation and publishing the configuration file using  `php artisan vendor:publish`. A default configuration file will be located under  `config/mailjet-mailer.php`.

<a name="account-env"></a>

#### Mailjet account

The `accounts` key can have many configurations. This can be very useful to setup different accounts for different environments (stages), e.g.  `local`, `development` or `production`. Especially if you are currently integrating Mailjet and do not want to risk your account being rate limited during development.

A  `default`  configuration is already set with all available options.

```php
return [
    'accounts' => [
	    // default to use in production
        'default' => [
            'key' => env('MAILJET_APIKEY'),
            'secret' => env('MAILJET_APISECRET'),
            'templates' => [
	            'example-template' => [
					'id' => 1,
					'language' => true,
					'variables' => []
				]	
			],
            'version' => env('MAILJET_MAILER_API', 'v3.1'),
            'sender' => [ 
                'email' => env('MAILJET_MAILER_FROM', env('MAIL_FROM', 'sender@example.com')),
                'name' => env('MAILJET_MAILER_FROM_NAME', env('MAIL_FROM_NAME', 'Mailjet Mailer'))
            ]
        ],
        // to be used in development (stages)
        'development' => [
            'key' => 'different-api-key',
            'secret' => 'different-api-secret',
            'version' => env('MAILJET_MAILER_API', 'v3.1'),
            'sender' => [
                'email' => 'development@example.com',
                'name' => 'Development Server'
            ]
        ],
        // to be used on a local environment
        'local' => [
	        // ... local details.
		],
    ]
];
```

To use one of these accounts, we simply set the [account environment](#mailjet-account-settings) variable.

```bash
MAILJET_MAILER_ACCOUNT=development
```

<br />

### E-mail interceptor

E-mail addresses can be whitelist per e-mail or domain. You can either use the environment variables or change the configuration to your likings.

```php
'interceptor' => [

    'enabled' => env('MAILJET_MAILER_INTERCEPT', false),
    
    'to' => [
        'email' => env('MAILJET_MAILER_INTERCEPT_TO', null),
        'name' => env('MAILJET_MAILER_INTERCEPT_TO_NAME', 'Mailjet Interceptor'),
    ],
    
    'whitelist' => [
        // e-mail adresses to be whitelisted
        'emails' => [
            'some@fake.email'
        ]
        // whitelist per domain.
        'domains' => [
            'fake.email'
        ]
    ]
]
```

<br />

## Basic usage

### Creating a simple request (@todo)

> Simple request using text and html examples. Supported in [v1.4](https://github.com/wize-wiz/laravel-mailjet-mailer/issues/4).

#### Setting the Send API version (@todo)

> Version `$Mailer = new Mailer(['version' => 'v3'])` or `$Request->version('v3')`, etc. Each new request created by the `$Mailer` instance will transfer all options to the request using `$Mailer->newRequest()`.
> Only `v3.1` is supported, see milestone [v1.5](https://github.com/wize-wiz/laravel-mailjet-mailer/milestone/2) for `v3` support.

#### Send API Sandbox (@todo)

> The API sandbox is only supported in `v3.1` of the Send API. Mailjet does not offer an equivalent option for `v3`. This package should emulate with fake responses to support sandboxing in general. `$Request->useSandbox()`, see milestone [v1.5](https://github.com/wize-wiz/laravel-mailjet-mailer/milestone/2)

#### Account  (@todo)

> The `Mailer` class can use all available accounts defined in `config/mailjet-mailer.php` on the fly. `new Mailer(['account' => 'local'])`.

#### Mailer and Request options (@todo)

> Create a minimal list of options.
 
<br />

## Notifiables

The notifiable class should implement the following [MailjetMessageable](src/Contracts/MailjetMessageable.php) interface and [HandlesMailjetMessageable](src/Concerns/HandlesMailjetMessageable.php) trait. Below an implementation example for the `User` model.

```php
use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;
use WizeWiz\MailjetMailer\Concerns\HandlesMailjetMessageable;

class User implements MailjetMessageable {
    use HandlesMailjetMessageable;
	... 
}
```

The  `MailjetMessageable`  uses two methods to determine the recipients e-mail and name. The  `HandlesMailjetMessageable` trait has a standard implementation to use the  `email`  and  `name` attributes by default. If your implementation of the model deviates in any way, just overwrite `getMailjetableEmailAttribute()` and/or `getMailjetableNameAttribute()` methods and return the corresponding attributes as a `string`.

```php
class User extends Authenticatable implements MailjetMessageable {
    
    use HandlesMailjetMessageable;

    /**
     * Return custom e-mail attribute.
     *  
     * @return string
     */
    public function getMailjetableEmailAttribute() : string {
        return 'e_mail';
    }

    /**
     * Return name attribute.
     *  
     * @return string
     */
    public function getMailjetableNameAttribute() : string {
        return 'fullname';
    }
}
```

If the user's name exists out of multiple attributes, e.g. `first_name` and `last_name`. Just add a getter attribute for `name` and leave the rest untouched.

```php
	public function getNameAttribute() {
		return "{$this-first_name} {$this->last_name}";
	}
``` 

To use the `User` model as a recipient, you simply add the model using `->notify($User)`. This is the equivalent of using `->to($User->mailjetableEmail(), $User->mailjetableName())`.

```php
use WizeWiz\MailjetMailer\Mailer;

$Mailer = new Mailer();
$Request = $Mailer->newRequest();
$User = User::find(123);
// add the user to the recipient (to) list.
$Request->notify($User);
```

The `->notify()` method also supports a [Collection](https://laravel.com/docs/7.x/collections) of `MailjetMessageable`s.

```php
// get random 10 users.
$Users = User::limit(10)->get();
// all users will receive the same e-mail and showup in the recipient (to) list.
$Request->notify($Users);
```

> Should notify detect the given argument does not implement the `MailjetMessageable` interface, an `InvalidNotifiableException` will be thrown.

### Retrieving made requests and messages

To retrieve all messages send to this user, simply call the relationship `mailjet_messages`.

```php
use App\User;

$User = User::find(1);
$User->mailjet_messages;
```

If [webhook events](#event-tracking-using-webhooks) are being tracked, you could filter messages based on the current status.

```php
use App\User;
use WizeWiz\MailjetMailer\Events\Webhook\WebhookEvent;

$User
	->mailjet_messages()
	->where('delivery_status', WebhookEvent::EVENT_SENT)
```

Or you could verify if a registration e-mail was sent, or event opened/clicked.	

```php
use App\User;
use WizeWiz\MailjetMailer\Events\Webhook\WebhookEvent;

$User
	->mailjet_messages()
	->where('template_name', 'registration')
	->where(function($query) {
		$query->where('delivery_status', WebhookEvent::EVENT_OPEN)
		  	  ->orWhere('delivery_status', WebhookEvent::EVENT_CLICK)
	})
	// get the latest
	->orderBy('created_at', 'desc')
	->count();
```

If the e-mail was bounced or filtered as spam, you could notify the user about it.

```php
$MailjetMessage = $User
	->mailjet_messages()
	->where('template_name', 'registration')
	// get the latest
	->orderBy('created_at', 'desc')
	->first();

if($MailjetMessage->isSpam() || $MailjetMessage->isBounced()) {
	// notify user ..
}
```

See the [message events](#message-events) for more information.

<br />

## Mailjet Passport templates

Any template created by [Mailjet Passport](https://www.mailjet.com/feature/passport/) can be defined in the `accounts` setting in the configuration. The `template` key has the following structure.

```php
'accounts' => [
    'default' => [
        ... 
        'templates' => [
            // key to describe the template.
            'example-template' => [
                'id' => 1000000, // id given by mailjet.
                'language' => true // if Mailjet's template engine should be used (MJML).
                'variables' => [
                    'showBackground' => false,
                    'text' => 'Some default text!'
                ] // predefine values for variables.
            ]
        ]
        ...
    ]
]
```

This template can now be used in a request.

```php
use WizeWiz\MailjetMailer\Mailer;

$Mailer = new Mailer();
$Request = $Mailer->newRequest();

$Request
	// set template to be used
	->template('example-template')
	// set the template variables
	->variables([
		'showBackground' => true,
		'text' => 'A different text?'
	])
	// set subject
	->subject('This is a subject.')
	// set recipient
	->to('some@fake.email', 'Recipients Name');
	
// send the request.
$Mailer->send($Request);
```

#### Collections

To avoid calling the Send API once for each single request, a `MailjetRequestCollection` can be used. The `MailjetRequestCollection` allows to send multiple requests with one single API call ([bulk e-mail](https://dev.mailjet.com/email/guides/send-api-v31/#send-in-bulk)).

```php
use WizeWiz\MailjetMailer\Mailer;

$Mailer = new Mailer();
$Collection = $Mailer->newCollection();
// each `newRequest()` call will create a new request instance and automatically added to the collection.
$Request1 = $Collection->newRequest();
$Request1
	->subject('Email one')
	->to('some@fake.email', 'Some User')
	
// another request.
$Request2 = $Collection->newRequest();
$Request2
	->subject('Email two')
	->to('another@fake.email', 'Another User');

// send two requests with only one API call.
$Mailer->send($Collection);
```

Any `MailjetRequest` can be converted to a `MailjetRequestCollection`. There are two ways of creating a `MailjetRequestCollcetion`. 

- `$Request->asCollection()` converts the `MailjetRequest` to a `MailjetRequestCollection` while adding the `MailjetRequest` to the newly created collection simply creates a new empty collection.

- `$Request->createCollection();` will simply create a new empty `MailjetRequestCollection`.

```php
$Mailer = new Mailer();
$Request = $Mailer->newRequest();
// a new collection with the added request $Request.
$FilledCollection = $Request->toCollection();
// a new empty collection
$EmptyCollection = $Request->createCollection();
```

> Please note that all instances of `Illuminate\Database\Eloquent\Model` implement a method called `newCollection()`. This simply returns a new empty `Illuminate\Database\Eloquent\Collection` instance.

A `MailjetRequestCollection` also has the advantage of assigning a [`Collection`](https://laravel.com/docs/7.x/collections) of notifiables, e.g. a collection of user models. 

The `->assign()` method was created to specify a predefined request and pass a clone of the request to the callback using a [`Collection`](https://laravel.com/docs/7.x/collections) of `MailjetMessageable`s.

```php
$Collection->assign($Users, $Request, function($User, $Request) {
	// cloned instance of $Request.
	return $Request
		->variables([
			'greeting' => "Hello {$User->name}"
		])
		->notify($User);
});
```

A complete example is shown here.

```php
use App\User;
use WizeWiz\MailjetMailer\Mailer;

$Mailer = new Mailer();
$Request = $Mailer->newRequest();
// predefine the request.
$Request
	->subject('News update!')
	->template('some-news-template')
	->variables([
		'showBackground' => true,
		'text' => 'Some wonderful updates for you!'
	]);
// some random collection of users
$Users = User::limit(10)->get();
// create a collection
$Collection = $Mailer->newCollection();
// assign each user to a freshly cloned request
$Collection->assign($Users, $Request, function($User, $Request) {
	return $Request
		->variables([
			'greeting' => "Hello {$User->name}"
		])
		->notify($User);
});
// send the collection to deliver all (10) requests with only one API call.
$Mailer->send($Collection);
```

If the `$Request` returned by the callback did not add any recipients (using `->to()` or `->notify()`), the `$User` who was applied for this callback will be automatically added to the `$Request` as a recipient.

It is also possible to use a [`Collection`](https://laravel.com/docs/7.x/collections)  of recipients.

```php
$recipients = collect([
	['name' => 'Recipient Name', 'email' => 'recipient@fake.email'],
	['name' => 'Another Recipient', 'email' => 'another@fake.email']
]);
$Collection->assign($recipients, $Request, function($recipient, $Request) {
	return $Request
		->variables([
			'greeting' => "Hello {$recipient['name']}"
		])
		->to($recipient['email'], $recipient['name']);
});
```

> If the `Collection` contains an array with an unsuitable recipient structure, an `InvalidRecipientException` will be thrown.

<br />

## E-mail with notifications

API requests can be made via the [Laravel's Notifications](https://laravel.com/docs/master/notifications) with a predefined abstract Notification class called  `MailjetNotification`.

Create a new notification class and register the  `MailjetChannel`  in the  `via` method. Create an additional `toMailjet` method to fill the  `MailjetRequest`.

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
     * Send an E-Mail via Mailjet's Send API. 
     * 
     * @param MailjetMessageable $notifiable
     * @param MailjetRequest $Request
     * @return MailjetRequest
     */
    public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequestable  {
        return $Request
            // template defined in our config/mailjet-mailer.php.
            ->template('register')
            // template variables defined at Mailjet's Passport.
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
            // .. any custom data to save with the notification.
        ];      
    }
}
```

If the model implements [Laravel's `Notifiable`](https://laravel.com/docs/7.x/notifications#using-the-notifiable-trait) trait, we could simply send a notification e-mail using:

```php
$User->notify(new RegisterNotification());
``` 

#### Using a collection (bulk)

Bulk e-mail uses a `MailjetRequestCollection` to send many e-mails with only one API call. Here we prepare one single request to be personalised for each user and convert it to a collection. The assign method accepts a collection of users (`MailjetMessageable`) to be notified, the prepared request and a callback function. Each `MailjetRequest` can be converted to a `MailjetRequestCollection` as [explained here](#collections).

```php
public function toMailjet(MailjetMessageable $notifiable, MailjetRequest $Request) : MailjetRequestable  {
    // simply get 10 users.
    $Users = User::limit(10)->get();
    // prepare the request
    $Request
        // template defined in our config/mailjet-mailer.php.
        ->template('register')
        // template variables defined at Mailjet's Passport.
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

<br />

## Event tracking using webhooks

Mailjet supports [event tracking using webhooks](https://dev.mailjet.com/email/guides/webhooks/) which makes it possible to track the status of each sent e-mail.

This package implements a complete webhook controller to track all events delivered by Mailjet. The supported events are: `Sent`, `Open`, `Click`, `Bounce`, `Blocked`, `Spam` and `Unsub`.

The default endpoint for the webhook controller is `api/mailjet/webhooks` with a default `api` middleware. You can customise these settings in the `config/mailjet-mailer.php` config. The webhook is by default **disabled**, to enable the webhook controller, just set `enabled` to `true`.

> Make sure to run `php artisan route:clear` if the routes are not visible or shown with `php artisan route:list`.

```php
return [
	'webhooks' => [
	  'enabled' => true,  
	  'middleware' => ['api'],  
	  'prefix' => 'api',
	  'endpoint' => 'mailjet/webhook'
	]	
]
```

> Because the Mailjet Webhook API does **not** provide any form of signature to verify the authenticity of the incoming webhook request, each request (literally each message) is stored in the database and compared against each incoming webhook request. This means each message is provided with a generated `CustomID`. 

> No matter the outcome, a `200 HTTP OK` response is always returned to avoid Mailjet resending the request every **30 seconds** for **24 hours**, which could lead to useless high loads.

#### Enable webhook events at Mailjet's Event API

In order for the webhook controller to receive events, it needs to be enabled at Mailjet's [Event API](https://app.mailjet.com/account/triggers). You can enable all checkboxes with just one endpoint, e.g. https://example.com/api/mailjet/webhook or configure as you see fit.

#### Retrieving events

You can retrieve an event simply by calling the relationship method `mailjet_webhook_events()` on a `MailjetMessage` model.

```php
MailjetMessage::find(1)->mailjet_webhook_events;
```

The `MailjetMessage` model has a bunch of event methods to easily figure out the status of a message.

- `$MailjetMessage->isSent()` if the message was sent, this includes `open`, `click`, `spam` and `unsub` events.
-  `$MailjetMessage->isOpened()` if the message was opened, this includes `click` and `unsub`. 
- `$MailjetMessage->isClicked()` if the CTA (Call To Action) button was clicked.
- `$MailjetMessage->isBounced()` if the e-mail was bounced. This includes soft and hard bounces.
	- `$MailjetMessage->isSoftBounce()` if the bounce has a temporary issue, e.g. timeouts or e-mail box full. Delivery will be tried within 5 days. After 5 days the message status will be set to a hard bounce. 
	- `$MailjetMessage->isHardBounce()` if the bounce has permanent issue, e.g. invalid e-mail addresses, non-existing destination servers.
	- `$MailjetMessage->getBounceReason()` get the reason why an e-mail bounced.
- `$MailjetMessage->isBlocked()` if the message was blocked. This error can have various reasons, e.g. so called pre-block which also possible spam or e-mails which have previously hard bounced, etc. 
	- `$MailjetMessage->getBlockedReason()` get the reason why an e-mail was blocked.
- `$MailjetMessage->isSpam()` if the e-mail was marked as spam.
	- `$MailjetMessage->getSpamSource()` get the source which marked this e-mail as spam. 
- `$MailjetMessage->isUnsubscribed()` if the user unsubscribed from the e-mail list _(this is not relevant for transactional e-mails)_.

<br />

### Delivery failure (backup)

> Not yet implemented. If Mailjet fails to deliver the e-mail or fails to connect with Mailjet's Send API, a backup can be configured to deliver the e-mail. The currently used library [mailjet/mailjet-apiv3-php](https://github.com/mailjet/mailjet-apiv3-php) isn't ideal to create a backup mechanism. It would only be possible to partially implement a fail-safe.

<br />

### Collaboration

If you would like to help improve this package in any way, send a pull request for updates, bug-fixes or features, or simply create an issue to explain your motivation of becoming a maintainer for this package.

See my profile if wish to contact me by e-mail.

<br />

### License

The MIT License (MIT). Please see  [License File](LICENSE.md)  for more information.