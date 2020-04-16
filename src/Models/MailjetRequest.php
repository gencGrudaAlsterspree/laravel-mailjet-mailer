<?php

namespace WizeWiz\MailjetMailer\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use WizeWiz\MailjetMailer\Concerns\UsesUuids;
use WizeWiz\MailjetMailer\Contracts\MailjetableModel;
use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;
use WizeWiz\MailjetMailer\Jobs\MailjetJobRequest;
use WizeWiz\MailjetMailer\Mailer;
use Illuminate\Database\Eloquent\Model;
use WizeWiz\MailjetMailer\MailerResponse;
use Mailjet\Response as MailjetLibResponse;

/**
 * Class MailjetRequest
 * @package WizeWiz\MailjetMailer\Models
 */
class MailjetRequest extends Model {

    use UsesUuids;

    protected $table = 'mailjet_requests';
    public $timestamps = true;

    protected $tries = 0;
    protected $max_tries = 3;

    /**
     * @var string If the message would be send into the real world.
     * @modes live, dry
     */
    private $mode = 'live';

    /**
     * @var bool
     */
    protected $sent = false;

    /**
     * @var bool
     */
    protected $failed = false;

    /**
     * @var bool
     */
    public $prepared = false;

    /**
     * @var null|array['email', 'name']
     */
    protected $sender;

    /**
     * @var array
     */
    protected $notifiables = [];

    /**
     * @var bool
     */
    protected $should_queue = false;

    /**
     * @var string|null
     */
    protected $queue_connection = null;

    /**
     * @var string
     */
    protected $queue_queue = null;

    /**
     * @var int|null
     */
    protected $queue_delay = null;

    /**
     * @var array
     */
    protected $fillable = [
        'from_name',
        'from_email',
        'recipients',
        'subject',
        'template_id',
        'template_name',
        'template_language',
        'variables',
        'status',
        'success',
        'version',
        'sandbox',
        'queue'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'recipients' => 'array',
        'template_language' => 'bool',
        'variables' => 'array',
        'success' => 'bool',
        'sandbox' => 'bool',
        'queue' => 'array'
    ];

    /**
     * @var array[MailjetMessage]
     */
    protected $messages = [];

    /**
     * MailjetRequest constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = []) {
        parent::__construct($this->withDefaults($attributes));
    }

    /**
     * Set model defaults.
     * @param array $attributes
     * @return array
     */
    protected function withDefaults(array $attributes) {
        if(!isset($attributes['status'])) {
            $attributes['status'] = 'creating';
        }
        $attributes['recipients'] = [];
        $attributes['variables'] = [];
        $attributes['success'] = false;

        return $attributes;
    }

    /**
     * Reinitialize from model.
     */
    public function reinitialize() {
        Log::info('reinitializing MailjetRequest.');
        if($this->isSaved()) {
            // reconstruct notifiables
            $this->notifiables = [];
            $users = $this->users;
            if($users) {
                foreach($users as $User) {
                    $this->notify($User);
                }
            }

            // reconstruct queue
            if(!empty($this->queue)) {
                $this->queue($this->queue);
            }

            switch($this->status) {
                case 'prepared':
                    $this->prepared = true;
                    $this->sent = false;
                    break;
                case 'sent':
                    $this->prepared = true;
                    $this->sent = true;
                    break;
                case 'failed':
                    $this->failed = true;
                    // @todo: when is it failed? after prepared or after sent?
                    $this->prepared = true;
                    $this->sent = true;
                    break;
                default:
                case null:
                    $this->prepared = false;
                    $this->sent = false;
            }
        }
    }

    /**
     * Create a job to process this request.
     * @param array $options
     * @param MailjetJobRequest
     */
    public function makeJob(array $options = []) {
        // create job
        $Job = new MailjetJobRequest($this, $options);
        if($this->queue_connection !== null) {
            $Job->onConnection($this->queue_connection);
        }
        if($this->queue_delay !== null) {
            $Job->delay($this->queue_delay);
        }
        return $Job;
    }

    /**
     * Return version.
     * @return mixed
     */
    public function getVersion() {
        return $this->version;
    }


    /**
     * Set mode.
     * @param $mode Modes: live | dry
     */
    public function mode($mode) {
        // @todo: check modes
        $this->mode = $mode;
        return $this;
    }

    /**
     * Return mode.
     * @return string
     */
    public function getMode() : string {
        return $this->mode;
    }

    /**
     * Should the Mailer be queued.
     * @return bool
     */
    public function shouldQueue() : bool {
        return $this->should_queue;
    }

    /**
     * Set queue.
     * @param $connection
     * @param $delay
     * @return $this
     */
    public function queue($connection = null, $queue = null, $delay = null) {
        if(is_array($connection)) {
            $delay = isset($connection['delay']) ? $connection['delay'] : $delay;
            $queue = isset($connection['queue']) ? $connection['queue'] : $queue;
            $connection = isset($connection['connection']) ? $connection['connection'] : null;
        }

        $this->should_queue = true;
        $this->queueConnection($connection);
        $this->queueQueue($queue);
        $this->queueDelay($delay);
        return $this;
    }

    /**
     * Set connection for the queue.
     * @param $connection
     * @return $this
     */
    public function queueConnection($connection) {
        $this->queue_connection = $connection;
        return $this;
    }

    /**
     * Return queue connection.
     * @return null|string
     */
    public function getQueueConnection() {
        return $this->queue_connection;
    }

    /**
     * If queue_connection was set.
     * @return bool
     */
    public function hasQueueConnection() {
        if($this->shouldQueue()) {
            return !empty($this->queue_connection);
        }
        return false;
    }

    /**
     * If queue_connection was set.
     * @return bool
     */
    public function hasQueueQueue() {
        if($this->shouldQueue()) {
            return !empty($this->queue_queue);
        }
        return false;
    }

    /**
     * If queue_connection was set.
     * @return bool
     */
    public function hasQueueDelay() {
        if($this->shouldQueue()) {
            return !empty($this->queue_delay);
        }
        return false;
    }

    /**
     * Set queue queue.
     * @param string $queue
     * @return $this
     */
    public function queueQueue($queue = 'default') {
        $this->queue_queue = $queue;
        return $this;
    }

    /**
     * Return queue queue.
     * @return string
     */
    public function getQueueQueue() {
        return $this->queue_queue;
    }

    /**
     * Set queue delay.
     * @param $delay
     * @return $this
     */
    public function queueDelay($delay) {
        $this->queue_delay = $delay;
        return $this;
    }

    /**
     * Return delay for queue.
     * @return null|integer
     */
    public function getQueueDelay() {
        return $this->queue_delay;
    }

    /**
     * @see https://dev.mailjet.com/email/guides/send-api-v31/#sandbox-mode
     * Turn sandbox on (only supported in v3.1)
     * @param bool $sandbox
     * @return Mailer
     */
    public function useSandbox($sandbox = true) {
        $this->sandbox = $sandbox;
        return $this;
    }

    /**
     * Return is call will is/will be sandboxed.
     * @return bool
     */
    public function isSandboxed() : bool {
        return $this->sandbox;
    }

    /**
     * If message was send.
     * @return bool
     */
    public function isSent() : bool {
        return $this->sent;
    }

    /**
     * Add a Notifiable object to the recipients list.
     * @todo: Can we hint with Eloquent\Model?
     * @param $notifiable
     * @return Mailer
     */
    public function notify(object $notifiable) {
        try {
            $email = null;
            $name = null;
            // default.
            // if model implements MailjetableModel, we can use the defined methods to get recipient data.
            if ($notifiable instanceof MailjetableModel) {
                list('email' => $email, 'name' => $name) = $notifiable->mailjetableRecipient();
            }
            // alternative.
            // try to get by default properties `email` and `name`.
            else {
                try {
                    $email = $notifiable->email;
                    $name = $notifiable->name;
                } catch (\Exception $e) {
                    $this->log($e->getMessage());
                }
            }
            if($this->hasNotifiable($email) === false) {
                // we always assume email is unique.
                $this->notifiables[$email] = $notifiable;
            }
            if($this->hasRecipient($email) === false) {
                // add recipient
                $this->recipient($email, $name);
            }
        } catch(\Exception $e) {
            $this->log($e->getMessage());
        }
        return $this;
    }

    /**
     * Set add recipient to the recipients list.
     * @todo: check and prevent diplicate to('email@email.com')->to('email@email.com')?
     * @param $recipient
     * @return Mailer
     */
    protected function recipient($email, $name) {
        $recipients = $this->recipients;
        $recipients[] = [
            'Email' => $email,
            'Name' => $name
        ];
        $this->attributes['recipients'] = json_encode($recipients);
    }

    /**
     * To custom E-Mail, if E-Mail is found as a notifiable, add notifiable.
     * @param $email
     * @param null $name
     * @return Mailer
     */
    public function to($email, $name = null) {
        $notifiable = $this->findNotifiable($email);
        if($notifiable) {
            return $this->notify($notifiable);
        }
        // check if recipient exists.
        return $this->hasRecipient($email) === false ?
            $this->recipient($email, $name) :
            $this;
    }

    /**
     * @param $email
     * @return bool
     */
    public function hasRecipient($email) : bool {
        $index = array_search($email, array_column($this->recipients, 'Email'));
        return $index !== false;
    }

    /**
     * @param $email
     * @return bool
     */
    public function hasNotifiable($email) : bool {
        return isset($this->notifiables[$email]);
    }

    /**
     * Set E-Mail from.
     *
     * @param $email
     * @param $name
     */
    public function sender($email, $name) {
        $this->sender = [
            'email' => $email,
            'name' => $name
        ];
        return $this;
    }

    /**
     * Set Mailjet template.
     * @param mixed $name Name or id of the template.
     */
    public function template($name) {
        if(is_string($name)) {
            $templates = (new Mailer())->getConfigOption('templates');
            if(array_key_exists($name, $templates) === false) {
                throw new \Exception("template with {$name} not defined in ".Mailer::CONFIG.".".$this->environment.".templates");
            }

            $this->template_name = $name;
            $template = $templates[$name];
            if(is_array($template)) {
                $this->templateId((int)@$template['id']);
                $this->templateLanguage(@$template['language']);
            }
            else {
                $this->template_id = (int)@$template;
            }
        }
        return $this;
    }

    /**
     * Return template name.
     * @return mixed
     */
    public function getTemplateName() {
        return $this->template_name;
    }

    /**
     * Set E-Mail subject.
     * @param $subject
     */
    public function subject($subject) {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set the template id.
     * @param $id
     * @return $this
     */
    public function templateId($id) {
        $this->template_id = $id;
        return $this;
    }

    /**
     * Set variables.
     * @param array $variables
     * @return $this
     */
    public function variables(array $variables) {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Use template language.
     * @param bool $language
     * @return Mailer
     */
    public function templateLanguage($language = true) {
        $this->template_language = $language;
        return $this;
    }

    /**
     * Return recipients.
     * @return mixed
     */
    public function getRecipients() {
        return $this->recipients;
    }

    /**
     * Return notifiables.
     * @return array
     */
    public function getNotifiables() {
        return $this->notifiables;
    }

    /**
     * If request was saved.
     * @return bool
     */
    public function isSaved() {
        return empty($this->id) === false;
    }

    /**
     * Save Request to database entry.
     * @param array $options
     * @return bool
     */
    public function save(array $options = []) {
        if(empty($this->id)) {
            $this->id = Str::uuid()->toString();
        }
        return parent::save($options);
    }

    /**
     * Prepare request.
     */
    public function prepare() {
        $this->prepared = true;
        // apply defaults if not manually set.
        $this->sender = array_merge(
            (new Mailer())->getConfigOption('sender'),
            $this->sender ?: []);
        // set attributes.
        $this->from_email = $this->sender['email'];
        $this->from_name = $this->sender['name'];
        // if request was not yet created, create now.
        if($this->isSaved() === false) {
            $this->status = 'prepared';

            // save queue
            $queue = [];
            if(!empty($this->hasQueueConnection())) {
                $queue['connection'] = $this->queue_connection;
            }
            if(!empty($this->hasQueueQueue())) {
                $queue['queue'] = $this->queue_queue;
            }
            if(!empty($this->hasQueueDelay())) {
                $queue['delay'] = $this->queue_delay;
            }
            $this->queue = $queue ?: null;

            // try to save the model.
            $saved = false;
            try {
                $saved = $this->save();
            // rollback status to none.
            } catch(\Exception $e) {
                Log::info("preparing request failed");
                Log::info($e->getMessage());
                Log::info($e->getFile() . ' @ ' . $e->getLine());
                $this->status = 'none';
                $this->id = null;
            }

            // if saved, attach/save messages.
            if($saved) {
                // attach notifiables.
                foreach($this->notifiables as $notifiable) {
                    $this->users()->attach($notifiable);
                }

                // create a message for each recipient.
                foreach($this->recipients as $recipient) {

                    $email = $recipient['Email'];
                    $message = new MailjetMessage([
                        'mailjet_request_id' => $this->id,
                        'email' => $email,
                        'version' => $this->version,
                        'status' => "none",
                        'delivery_status' => "none",
                        'sandbox' => $this->sandbox
                    ]);

                    // attach message to notifiables.
                    $this->messages[] = $message;
                    if(isset($this->notifiables[$email]) &&
                        $this->notifiables[$email] instanceof MailjetMessageable) {
                        $this->notifiables[$email]
                            ->mailjet_messages()
                            ->save($message);
                    }
                }
            }
        }
    }

    /**
     * If Request was prepared.
     * @return mixed
     */
    public function isPrepared() {
        return $this->prepared;
    }

    /**
     * Update the request with given response.
     */
    public function updateFromResponse($error,  MailerResponse $Response, MailjetLibResponse $LibResponse) {
        // request was sent
        if($error === false) {
            // mark request as sent
            $this->markAsSent();
            $data = [
                'status' => 'waiting',
                'success' => true
            ];
        }
        else {
            // mark as failed
            $this->markAsFailed();
            $data = [
                'status' => 'failed',
                'success' => false
            ];
        }
        // update request
        if(($updated = $this->update($data))) {
            // add delivery status for message.
            $data['delivery_status'] = 'waiting';
            // update messages
            foreach($this->mailjet_messages as $message) {
                $message->update($data);
            }
        }
        return $updated;
    }

    /**
     * Build API request body.
     * @param $version
     * @return array
     */
    public function buildBody() : array {
        switch($this->version) {
            case Mailer::VERSION_3:
                return $this->buildBodyVersion3();
                break;
            case Mailer::VERSION_31:
                return $this->buildBodyVersion31();
                break;
        }
    }

    /**
     * Build body according to Send API v3
     * resource: https://dev.mailjet.com/email/guides/send-api-V3/
     */
    protected function buildBodyVersion3() : array {
        $body = [];
        $message = [];

        $message['Mj-CustomID'] = static::generateCustomId();

        return $body;
    }

    /**
     * Build body according to Send API v3.1
     * @resource: https://dev.mailjet.com/email/guides/send-api-v31/
     * @todo: multiple messages (bulk)
     */
    protected function buildBodyVersion31() : array {
        $body = [
            'Messages' => []
        ];
        // create message
        $message = [
            'From' => $this->sender,
            'To' => $this->recipients,
            'Subject' => $this->subject
        ];
        // set template
        if(!empty($this->template_id)) {
            $message['TemplateID'] = $this->template_id;
        }
        $message['TemplateLanguage'] = $this->template_language;
        // set variables if template_language is used
        if($this->template_language !== false && !empty($this->variables)) {
            $message['Variables'] = $this->variables;
        }
        // attach a custom id.
        $message['CustomID'] = $this->id;
        // append message
        $body['Messages'][] = $message;
        // sandbox mode
        if($this->sandbox === true) {
            $body["SandboxMode"] = true;
        }

        return $body;
    }

    /**
     * Personalized logger.
     * @param $msg
     */
    protected function log($msg) {
        if($this->debug) {
            Log::info('MailjetMailer: ' . $msg);
        }
    }

    /**
     * Find notifiable by E-Mail.
     * @param $email
     * @return null|object
     */
    protected function findNotifiable($email) {
        try {
            $user_model = $this->getUserModel();
            return $user_model::whereEmail($email)->first();
        } catch(\Exception $e) {
            return null;
        }
    }

    /**
     * Mark request as sent.
     * @param bool $sent
     * @return $this
     */
    protected function markAsSent($sent = true) {
        $this->failed = !$sent;
        $this->sent = $sent;
        return $this;
    }

    /**
     * Mark request as failed
     * @param bool $failed
     * @return $this
     */
    protected function markAsFailed($failed = true) {
        $this->sent = !$failed;
        $this->failed = $failed;
        return $this;
    }

    /**
     * Get the user model declared in this laravel instance.
     * @return string
     */
    protected function getUserModel() {
        return config('auth.providers.users.model');
    }

    /**
     * Get users for request
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users() {
        $user_model = $this->getUserModel();
        return $this->morphedByMany($user_model, 'mailjet_notifiable', 'mailjet_notifiables');
    }

    /**
     * Get users as recipients.
     */
    public function usersAsRecipients() {
        return $this->users->map(function($user) {
            return $user->only('id', 'email', 'name');
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mailjet_messages() {
        return $this->hasMany(MailjetMessage::class);
    }

    /**
     *
     */
    public function mailjet_webhook_events() {
       return $this->hasMany(MailjetWebhookEvent::class);
    }

    /**
     * Increment tries.
     */
    public function tried() {
        $this->tries++;
        if($this->tries > $this->max_tries) {
            throw new \Exception("Request exit, max tries of {$this->max_tries} reached.");
        }
    }
}