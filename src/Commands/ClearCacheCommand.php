<?php

namespace WizeWiz\MailjetMailer\Commands;

use Illuminate\Console\Command;
use WizeWiz\MailjetMailer\Mailer;

/**
 * A command to add \Eloquent mixin to Eloquent\Model
 *
 * @author Charles A. Peterson <artistan@gmail.com>
 */
class ClearCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mailjet-mailer:clear-cache {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear cached content produced by Mailjet Mailer.';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        // @todo: clear cached content?
        // @note: most content is not cached anymore due to insufficient performance differences.
    }
}
