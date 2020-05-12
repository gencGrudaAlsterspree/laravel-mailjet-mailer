<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Orchestra\Testbench\TestCase;
use WizeWiz\MailjetMailer\Events\EmailError;
use WizeWiz\MailjetMailer\Events\EmailSend;
use WizeWiz\MailjetMailer\Events\InvalidRecipientNotice;
use WizeWiz\MailjetMailer\Events\Webhook\BaseEvent;
use WizeWiz\MailjetMailer\Exceptions\InvalidNotifiableException;
use WizeWiz\MailjetMailer\Mailer;
use WizeWiz\MailjetMailer\Models\MailjetMessage;
use WizeWiz\MailjetMailer\Models\MailjetRequest;
use WizeWiz\MailjetMailer\Tests\FakeEvents;
use WizeWiz\MailjetMailer\Tests\FakeMailer;
use WizeWiz\MailjetMailer\Tests\FakeModels;
use WizeWiz\MailjetMailer\Tests\TestEnvironment;

class RequestTest extends TestCase {

    use WithoutMiddleware, RefreshDatabase, TestEnvironment;

    protected function ignoreTest() {
        return false;
    }

    public function testRequestSuccess() {
        $Mailer = new FakeMailer();
        $Models = new FakeModels();

        // first notifiable will not exist in the database (only as Model object).
        $FirstNotifiable = $Models->createFakeUser('fakeuser@fake.email.local');
        // second notifiable will exist in the database.
        $SecondNotifiable = $Models->createFakeUser('secondfake@fake.email.local', true);
        // we're going to prepare a single request
        $Request = $Mailer->newRequest();
        // we're not going to use a sandbox
        $this->assertFalse($Request->isSandboxed());
        // fill request.
        $Request
            ->subject('test subject')
            ->template('test-template')
            ->variables([
                // overwriting the boolean to false (default in template true)
                'boolean' => false
            ]);
        // should have no recipients or notifiables.
        $this->assertFalse($Request->hasRecipients());
        $this->assertEquals([], $Request->gatherRecipients());
        $this->assertFalse($Request->hasNotifiable('fakeuser@fake.email.local'));

        // add notifiable
        $Request->notify($FirstNotifiable);
        $this->assertTrue($Request->hasRecipients());
        $this->assertFalse($Request->hasNotifiable('testuser@fake.email.local'));
        $this->assertTrue($Request->hasNotifiable('fakeuser@fake.email.local'));

        // check recipients format
        $this->assertEquals([
            'email' => $FirstNotifiable->email,
            'name' => $FirstNotifiable->name
        ], $Request->gatherRecipients()[0]);

        // does not exist in the database, have to add to recipient list.
        $Request->to('recipient@fake.email.local', 'Another Recipient');
        // add second notifiable
        $Request->notify($SecondNotifiable);

        // we should have 2 notifiables and 3 recipients (1 as plain mail/name).
        $this->assertEquals(2, count($Request->getNotifiables()));
        $this->assertEquals(3, count($Request->getRecipients()));
        $this->assertEquals([
            'email' => $SecondNotifiable->email,
            'name' => $SecondNotifiable->name
            // should be positioned second.
        ], $Request->gatherRecipients()[2]);

        // adding a random object via notify should trigger an exception + event

        $this->expectException(InvalidNotifiableException::class);
        $this->expectsEvents(InvalidRecipientNotice::class);
        $Request->notify(new \stdClass());

        // request should not have been saved
        $this->assertFalse($Request->isSaved());

        // EmailSend event should be triggered when pushed to send.
        $this->expectsEvents(EmailSend::class);
        $Mailer->send($Request);
        // refresh model after save.
        $Request->refresh();
        // should be sent.
        $this->assertTrue($Request->isSent());
        $this->assertTrue($Request->isSaved());
    }

}