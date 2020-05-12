<?php

namespace WizeWiz\MailjetMailer\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;

trait TestEnvironment {

    static $CHANNEL = 'mailjet-mailer-test';

    protected function getEnvironmentSetUp($app) {
        // set test database.
        $this->loadDatabase($app);
        // set test configuration.
        $this->loadConfig($app);
        // set test log file channel.
        $this->loadLogging($app);
        $this->log('Environment setup complete.');
    }

    protected function ignoreTest() {
        return false;
    }

    protected function setUp(): void {
        parent::setUp();
        if($this->ignoreTest()) {
            $this->markTestIncomplete('@todo');
            return;
        }
        $this->artisan('migrate:refresh', ['--database' => 'mysql_testbench'])->run();
        // migrations and factories are loaded from `./src/Tests`. Not ./tests/whatever.
        $this->loadMigrationsFrom(realpath(__DIR__ . '/migrations'));
        $this->withFactories(realpath(__DIR__ . '/factories'));
    }

    protected function getPackageProviders($app) {
        return ['WizeWiz\MailjetMailer\MailjetMailerServiceProvider'];
    }

    protected function loadLogging(Application $app) {
        $app['config']->set('logging.channels.' . static::$CHANNEL, [
            'driver' => 'single',
            'path' => storage_path('logs/mailjet-mailer-test.log'),
            'level' => 'info',
        ]);
    }

    /**
     * Load test database.
     * @param Application $app
     */
    protected function loadDatabase(Application $app) {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'mysql_testbench');
        $app['config']->set('database.connections.mysql_testbench', [
            'host' => '192.168.10.10',
            'username' => 'orchestra_testbench',
            'password' => '1234',
            'driver' => 'mysql',
            'database' => 'orchestra_testbench',
            'prefix' => '',
        ]);

    }

    /**
     * Load test configuration.
     * @param Application $app
     */
    protected function loadConfig(Application $app) {
        // mailjt configuration
        $app['config']->set('mailjet-mailer.environment', 'local');
        $app['config']->set('mailjet-mailer.account', 'default');
        $app['config']->set('mailjet-mailer.accounts', [
            // default versio for Send API v3.1
            'default' => [
                'key' => 'default-fake-key',
                'secret' => 'default-fake-secret',
                'templates' => [
                    'test-template' => [
                        'id' => 1000000,
                        'language' => true,
                        'variables' => [
                            'test-var' => 'A test variable (default)',
                            'boolean' => true
                        ]
                    ]
                ],
                'version' => 'v3.1',
                'sender' => [
                    'email' => 'default@fake.email.local',
                    'name' => 'no-reply'
                ]
            ],
            // test version for Send API v3
            'test' => [
                'key' => 'test-fake-key',
                'secret' => 'test-fake-secret',
                'templates' => [
                    'test-template' => [
                        'id' => 2000000,
                        'language' => false,
                        'variables' => [
                            'test-var' => 'A test variable (test)',
                            'boolean' => false
                        ]
                    ]
                ],
                'version' => 'v3',
                'sender' => [
                    'email' => 'test@fake.email.local',
                    'name' => 'no-reply (test)'
                ]
            ],
        ]);
    }

    /**
     * Log file.
     */
    protected function Log($msg) {
        Log::channel(static::$CHANNEL)->info($msg);
    }

}