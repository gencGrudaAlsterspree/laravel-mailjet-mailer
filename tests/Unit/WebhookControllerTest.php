<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Orchestra\Testbench\TestCase;
use WizeWiz\MailjetMailer\Events\Webhook\WebhookEvent;
use WizeWiz\MailjetMailer\Mailer;
use WizeWiz\MailjetMailer\Models\MailjetMessage;
use WizeWiz\MailjetMailer\Models\MailjetRequest;
use WizeWiz\MailjetMailer\Tests\FakeEvents;
use WizeWiz\MailjetMailer\Tests\FakeMailer;
use WizeWiz\MailjetMailer\Tests\FakeModels;
use WizeWiz\MailjetMailer\Tests\TestEnvironment;

class WebhookControllerTest extends TestCase {

    use WithoutMiddleware,
        RefreshDatabase,
        TestEnvironment;

    protected function ignoreTest() {
        return false;
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testInvalidEvents() {
        $FakeEvents = new FakeEvents;
        // create instance of Mailer to initialize config.
        $Mailer = new Mailer();

        // invalid event data, missing CustomID
        $event = $FakeEvents->generateFakeEventData('invalid-event-data', [
            'email' => 'example@fake.email.local'
        ]);
        $response = $this->call('POST', '/api/mailjet/webhook', $event);
        $this->assertEquals(200, $response->status());
        $this->assertEquals('invalid-event-data', $response->getContent());

        // invalid event name
        $response = $this->call('POST', '/api/mailjet/webhook', $FakeEvents->generateFakeEventData('unknown-event', [
            'email' => 'example@test.com'
        ]));
        $this->assertEquals(200, $response->status());
        $this->assertEquals('invalid-event-name', $response->getContent());

        // invalid event payload, custom id does not exist.
        $response = $this->call('POST', '/api/mailjet/webhook', $FakeEvents->generateFakeEventData('sent', [
            'CustomID' => 'some-custom-id-that-does-not-exist',
            'email' => 'example@test.com'
        ]));
        $this->assertEquals(200, $response->status());
        $this->assertEquals('invalid-event-payload', $response->getContent());
    }

    /**
     * Test events.
     */
    public function testEvents() {
        $Events = new FakeEvents;
        $Models = new FakeModels;
        $Mailer = new FakeMailer();
        $User = $Models->createFakeUser('thisisa@fake.email.local');
        $Request = $Mailer->newRequest();
        $Request
            ->subject('Test subject')
            ->template('test-template')
            ->variables([
                'test-var' => 'testing 1 2 3 4 ..'
            ])
            ->notify($User);
        $Response = $Mailer->send($Request);

        $this->assertTrue($Response->success());
        $this->assertEquals('success', $Request->status);
        $this->assertEquals(Mailer::DEFAULT_VERSION, $Request->version);
        // if prepared, a message should be attached
        $MailjetMessage = $Request->mailjet_messages->first();
        // should be valid
        $this->assertInstanceOf(MailjetMessage::class, $MailjetMessage);
        // default version was set.
        $this->assertEquals(Mailer::DEFAULT_VERSION, $MailjetMessage->version);
        // message should be on EVENT_WAITING until event was received.
        $this->assertEquals(WebhookEvent::EVENT_WAITING, $MailjetMessage->delivery_status);

        // this would be the exepected sequence of events.
        foreach([WebhookEvent::EVENT_SENT, WebhookEvent::EVENT_OPEN, WebhookEvent::EVENT_CLICK] as $event_name) {
            // setting the delivery status to `sent`
            $response = $this->call('POST', '/api/mailjet/webhook', $Events->generateFromMessage($event_name, $MailjetMessage, false));
            $this->assertEquals(200, $response->status());
            $this->assertEquals('ok-'.$event_name, $response->getContent());
            // refreshing the model due to possible event update.
            $MailjetMessage->refresh();
            $this->assertEquals($event_name, $MailjetMessage->delivery_status);
        }

        // if these events occure due to second devices, nothing should be updated.
        foreach([WebhookEvent::EVENT_SENT, WebhookEvent::EVENT_OPEN] as $event_name) {
            // setting the delivery status to `sent`
            $response = $this->call('POST', '/api/mailjet/webhook', $Events->generateFromMessage($event_name, $MailjetMessage, false));
            $this->assertEquals(200, $response->status());
            $this->assertEquals('ok-'.$event_name, $response->getContent());
            // refreshing the model due to possible event update.
            $MailjetMessage->refresh();
            // these events should not have affect the delivery status.
            $this->assertEquals(WebhookEvent::EVENT_CLICK, $MailjetMessage->delivery_status);
        }
        // reset delete events
        $Request->resetStatus(MailjetRequest::STATUS_PREPARED);

        /**
         * testing multiple events, open should be set and sent should be ignored.
         */
        $response = $this->call('POST', '/api/mailjet/webhook', $Events->generateMultipleFromMessage(['open', 'sent'], $MailjetMessage, false));
        $this->assertEquals(200, $response->status());
        // for multiple, we just return `ok`
        $this->assertEquals('ok', $response->getContent());
        // refreshing the model due to possible event update.
        $MailjetMessage->refresh();
        $this->assertEquals('open', $MailjetMessage->delivery_status);

        // testing multiple events, click should be set and open should be ignored.
        $response = $this->call('POST', '/api/mailjet/webhook', $Events->generateMultipleFromMessage(['click', 'open'], $MailjetMessage, false));
        $this->assertEquals(200, $response->status());
        // for multiple, we just return `ok`
        $this->assertEquals('ok', $response->getContent());
        // refreshing the model due to possible event update.
        $MailjetMessage->refresh();
        $this->assertEquals('click', $MailjetMessage->delivery_status);
    }

    /**
     * @throws \Exception
     */
    public function testMultiMessageEvents() {
        $Events = new FakeEvents;
        $Models = new FakeModels;
        $Mailer = new FakeMailer();
        $Users = $Models->createFakeUsers(['thisisa@fake.email.local', 'another@fake.email.local']);
        $Collection = $Mailer->newCollection();
        $Request = $Collection->newRequest();
        $Request
            ->subject('Test subject')
            ->template('test-template')
            ->variables([
                'test-var' => 'testing 1 2 3 4 ..'
            ]);

        $Collection->assign($Users, $Request, function($Notifiable, $Request) {
            return $Request
                ->notify($Notifiable)
                ->variable('personal', 'Hello ' . $Notifiable->name);
        });
        // send requests.
        $Response = $Mailer->send($Collection);
        // refresh all requests in collection.
        $Collection->refresh();

        $this->assertTrue($Response->success());
        $this->assertEquals('success', collect($Collection->status)->first());
        $this->assertEquals(Mailer::DEFAULT_VERSION, $Request->version);
        // if prepared, a message should be attached
        foreach($Collection as $Request) {
            // one message per request.
            $MailjetMessage = $Request->mailjet_messages->first();
            // should be valid
            $this->assertInstanceOf(MailjetMessage::class, $MailjetMessage);
            // default version was set.
            $this->assertEquals(Mailer::DEFAULT_VERSION, $MailjetMessage->version);
            // message should be on EVENT_WAITING until event was received.
            $this->assertEquals(WebhookEvent::EVENT_WAITING, $MailjetMessage->delivery_status);
        }

        // setting the delivery status to `sent`
        $MailjetMessages = $Collection->mailjet_messages();
        $response = $this->call('POST', '/api/mailjet/webhook', $Events->generateMultipleFromMessages(['sent', 'open'], $MailjetMessages));
        $this->assertEquals(200, $response->status());
        $this->assertEquals('ok', $response->getContent());
        foreach($MailjetMessages as $MailjetMessage) {
            // refreshing the model due to possible event update.
            $MailjetMessage->refresh();
            $this->assertEquals('open', $MailjetMessage->delivery_status);
        }
    }
}

