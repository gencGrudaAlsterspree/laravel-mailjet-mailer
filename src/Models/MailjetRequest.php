<?php

namespace WizeWiz\MailjetMailer\Models;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use WizeWiz\MailjetMailer\Concerns\HandlesQueue;
use WizeWiz\MailjetMailer\Concerns\HandlesRequestable;
use WizeWiz\MailjetMailer\Concerns\UsesUuids;
use WizeWiz\MailjetMailer\Contracts\MailjetableModel;
use WizeWiz\MailjetMailer\Contracts\MailjetMessageable;
use WizeWiz\MailjetMailer\Contracts\MailjetRequestable;
use WizeWiz\MailjetMailer\Events\InvalidRecipientNotice;
use WizeWiz\MailjetMailer\Events\UnsavedUserNotice;
use WizeWiz\MailjetMailer\Exceptions\InvalidNotifiableException;
use WizeWiz\MailjetMailer\Mailer;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MailjetRequest
 * @package WizeWiz\MailjetMailer\Models
 */
class MailjetRequest extends Model implements MailjetRequestable {

    use UsesUuids,
        HandlesQueue,
        HandlesRequestable;

    const STATUS_PREPARED = 'prepared';
    const STATUS_FAILED = 'failed';
    const STATUS_SUCCESS = 'success';

    protected $table = 'mailjet_requests';
    public $timestamps = true;

    protected $tries = 0;
    protected $max_tries = 3;

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
     * @var array
     */
    protected $template_variables = [];
    
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
     * @var array
     */
    protected $template = [];

    /**
     * MailjetRequest constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = []) {
        parent::__construct($this->withDefaults($attributes));
    }

    /**s
     * Get variables by applying the default template variables supplied in the config.
     * @return array
     */
    public function getVariablesAttribute() {
        return array_merge($this->template_variables, json_decode($this->attributes['variables'], true));
    }

    public function availableTemplates() {
        return (new Mailer())->getConfigOption('templates');
    }

    /**
     * Set model defaults.
     * @param array $attributes
     * @return array
     */
    protected function withDefaults(array $attributes) : array {
        if(!isset($attributes['status'])) {
            $attributes['status'] = 'creating';
        }
        $attributes['recipients'] = [];
        $attributes['variables'] = [];
        $attributes['success'] = false;
        $attributes['sandbox'] = false;
        $attributes['subject'] = null;

        // apply defaults if not manually set.
        $this->sender = array_merge(
            (new Mailer())->getConfigOption('sender'),
            $this->sender ?: []);
        // set attributes.
        $attributes['from_email'] = $this->sender['email'];
        $attributes['from_name'] = $this->sender['name'];

        return $attributes;
    }

    /**
     * Reinitialize a request, e.g. when serialized in a queue.
     */
    public function reinitialize() : void {
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
                case static::STATUS_PREPARED:
                    $this->prepared = true;
                    $this->sent = false;
                    break;
                case static::STATUS_SUCCESS:
                    $this->prepared = true;
                    $this->sent = true;
                    break;
                case static::STATUS_FAILED:
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
     * Add a Notifiable object to the recipients list.
     * @throws InvalidNotifiableException
     * @return Mailer
     */
    public function notify($notifiable) {
        $notifiables = [];
        $num_args = func_num_args();
        if($num_args === 1) {
            if (is_iterable($notifiable)) {
                $notifiables = $notifiable;
            } else {
                $notifiables = [$notifiable];
            }
        }
        elseif ($num_args > 1) {
            $notifiables = func_get_args();
        }
        unset($notifiable);

        foreach($notifiables as $Notifiable) {
            if(!$Notifiable instanceof MailjetMessageable) {
                event(new InvalidRecipientNotice($Notifiable));
                throw new InvalidNotifiableException();
            }
            try {

                $email = null;
                $name = null;
                // default.
                // if model implements MailjetableModel, we can use the defined methods to get recipient data.
                if ($Notifiable instanceof MailjetableModel) {
                    list('email' => $email, 'name' => $name) = $Notifiable->mailjetableRecipient();
                }
                // alternative.
                // try to get by default properties `email` and `name`.
                else {
                    try {
                        $email = $Notifiable->email;
                        $name = $Notifiable->name;
                    } catch (\Exception $e) {
                        $this->log($e->getMessage());
                        event(new InvalidRecipientNotice($Notifiable, $e));
                    }
                }
                if ($this->hasNotifiable($email) === false) {
                    // we always assume email is unique.
                    $this->notifiables[$email] = $Notifiable;
                }
                if ($this->hasRecipient($email) === false) {
                    // add recipient
                    $this->recipient($email, $name);
                }
            } catch (\Exception $e) {
                $this->log($e->getMessage());
                event(new InvalidRecipientNotice($Notifiable, $e));
            }
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
            'email' => $email,
            'name' => $name
        ];
        $this->attributes['recipients'] = json_encode($recipients);
        return $this;
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
                throw new \Exception("template with {$name} not defined in ".Mailer::PACKAGE.".".$this->environment.".templates");
            }
            $this->template_name = $name;
            $this->template = $template = $templates[$name];

            // template settings was given.
            if(is_array($template)) {
                if(!isset($template['id'])) {
                    throw new \Excception("template '{$name}': missing id.");
                }

                $this
                     // set the corresponding template id provided by Mailjet.
                     ->templateId($template['id'])
                     // set template variables.
                     ->templateVariables((array) (isset($template['variables']) ? $template['variables'] : []))
                     // set template language, if template variables were given, set to true, false otherwise.
                     ->templateLanguage(isset($template['language']) ? $template['language'] : (!empty($this->template_variables) ? true : false));
            }
            // we assume a template id was given.
            else {
                $this->templateId($template);
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
        $this->template_id = (int)$id;
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
     * Set a key in $this->variables with given value.
     * @param $key
     * @param $value
     */
    public function variable($key, $value) {
        $this->attributes['variables'] = json_encode(array_merge($this->variables, [$key => $value]));
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
     * Use template variables.
     * @param array $variables
     * @return Mailer
     */
    public function templateVariables(array $variables) {
        $this->template_variables = $variables;
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
     * If recipient by $email exists.
     * @param $email
     * @return bool
     */
    public function hasRecipient($email) : bool {
        return array_search($email, array_column($this->recipients, 'email')) !== false;
    }

    /**
     * Has recipients.
     * @return bool
     */
    public function hasRecipients() : bool {
        return !empty($this->recipients);
    }

    /**
     * Gather all recipients, check if recipients needs to be intercepted.
     */
    public function gatherRecipients() {
        if(($interceptor = $this->shouldIntercept()) !== false) {
            $whitelist = isset($interceptor['whitelist']) ? $interceptor['whitelist'] : [
                'emails' => [],
                'domains' => []
            ];

            $emails = isset($whitelist['emails']) ? $whitelist['emails'] : [];
            $domains = isset($whitelist['domains']) ? $whitelist['domains'] : [];
            // @todo: exception if empty?
            $to = isset($interceptor['to']) ? $interceptor['to'] : [];

            $recipients = [];
            foreach($this->getRecipients() as $index => $recipient) {
                if($this->blackListed($recipient['email'], $emails, $domains)) {
                    // intercept recipient.
                    $recipients[$index ] = [
                        'email' => $to['email'],
                        'name' => $to['name'] . " ({$recipient['name']}:{$recipient['email']})"
                    ];
                }
                else {
                    $recipients[$index] = $recipient;
                }
            }
            // set new recipients.
            $this->attributes['recipients'] = json_encode($recipients);
        }
        return $this->recipients;
    }

    /**
     * If recipient is black listed.
     * @param $email
     * @param array $emails
     * @param array $domains
     * @return bool
     */
    public function blackListed($email, array $emails, array $domains) {
        if($emails && in_array($email, $emails)) {
            // whitelisted
            return false;
        }
        if($domains && (
            ( $split = explode('@', $email) ) &&
            // second index should be the domain
            isset($split[1]) && in_array($split[1], $domains)
        )) {
            // whitelisted
            return false;
        }
        // blacklisted
        return true;
    }

    /**
     * Should E-Mail be intercepted by modifying the recipient.
     */
    protected function shouldIntercept() {
        $interceptor = config(Mailer::PACKAGE . '.interceptor', false);
        if($interceptor && isset($interceptor['enabled']) && $interceptor['enabled'] === true) {
            return $interceptor;
        }
        return false;
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
                    try {
                        $this->users()->attach($notifiable);
                    // @todo: ignore non-saved users?
                    } catch(QueryException $e) {
                    } finally {
                        event(new UnsavedUserNotice($notifiable));
                    }
                }

                // create a message for each recipient.
                foreach($this->recipients as $recipient) {
                    $email = $recipient['email'];
                    $message = new MailjetMessage([
                        'mailjet_request_id' => $this->id,
                        'email' => $email,
                        'version' => $this->version,
                        'sandbox' => $this->sandbox
                    ]);

                    // attach message to notifiables.
                    $this->messages[] = $message;
                    if(isset($this->notifiables[$email]) &&
                        $this->notifiables[$email] instanceof MailjetMessageable) {
                        try {
                            $this->notifiables[$email]->mailjet_messages()->save($message);
                        } catch(QueryException $e) {
                        } finally {
                            event(new UnsavedUserNotice($notifiable));
                        }
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
        // @todo: implement v3 Send API
        $body = [];
        $message = [];

        $message['Mj-CustomID'] = '';

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
            'To' => [],
            'Subject' => $this->subject
        ];
        // set recipients.
        foreach($this->gatherRecipients() as $recipient) {
            $message['To'][] = [
                'Email' => $recipient['email'],
                'Name' => $recipient['name']
            ];
        }
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
            // @todo: get method to retrieve email attribute from model.
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