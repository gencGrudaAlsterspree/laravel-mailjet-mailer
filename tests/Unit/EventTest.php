<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Orchestra\Testbench\TestCase;
use WizeWiz\MailjetMailer\Events\Webhook\BaseEvent;
use WizeWiz\MailjetMailer\Mailer;
use WizeWiz\MailjetMailer\Models\MailjetMessage;
use WizeWiz\MailjetMailer\Models\MailjetRequest;
use WizeWiz\MailjetMailer\Tests\FakeEvents;
use WizeWiz\MailjetMailer\Tests\FakeMailer;
use WizeWiz\MailjetMailer\Tests\FakeModels;
use WizeWiz\MailjetMailer\Tests\TestEnvironment;

class EventTest extends TestCase {

    use WithoutMiddleware, RefreshDatabase, TestEnvironment;

    protected function ignoreTest() {
        return true;
    }

    public function testEvent() {
        $this->markTestIncomplete('@todo');
    }
}