<?php

namespace WizeWiz\MailjetMailer\Tests\Mailjet;
/**
 * Thank you for whoever made this Mailjet\Client absolutely useless!
 */

use Mailjet\Client;
use Mailjet\Config;
use WizeWiz\MailjetMailer\Tests\Mailjet\FakeRequest as Request;
use Mailjet\Response;

/**
 * @Maijet development team:
 * So for starters ..
 *
 * class A {
 *      // @mailjet-dev: absolute private to class A, yes ..
 *      private function _call() { ... }
 *
 *      // @mailjet-dev: public, we know what it does right? opposite of private ..
 *      public function post() {
 *          // will always call A:_call! There is absolutely no reason for this!
 *          $this->_call()
 *      }
 * }
 *
 * class B extends A {
 *      // will do horseshit!
 *      private function _call() {
 *          return 'https://www.youtube.com/watch?v=_I6AAguhMsM';
 *      }
 * }
 *
 *      // @mailjet-dev: protected? eh? oh yeah! something like https://www.youtube.com/watch?v=GVN17U3Vg34
 *      // @me: if you wish to make a trivial, not overridable method by design/choice/whatever, at least
 *      //      act like you know what you're doing!
 *      // @source https://www.php.net/manual/en/language.oop5.final.php
 *      final protected _call() { ... }
 *      // < or if you got the idea but just want to use private for privates sake ..
 *      final private _call() { ... }
 *
 *      // @mailjet-dev: what? then what the heck is private for?
 *      // @me: well .. let's say you use a method in class A's constructor and it is vital to keep class integrity, use private!
 *      public function __construct() {
 *          // keep integrity save.
 *          $this->privateMethodInClassA(); // !
 *      }
 *
 *      // @mailjet-dev: so private is quite shitty?
 *      // @me: oh it gets worse! If class A has a private method `setSettings`, this method is useless in any other class
 *      //      who extends from class A.
 *
 * class A {
 *      private function setSettings() { ... }
 * }
 *
 * class B {
 *      public function horseshit() {
 *          $this->setSettings(); // absolutely useless!
 *      }
 * }
 *
 *      // @mailjet-dev: so if I would just write a bunch of privates for no reason, this class is not extendable? As in useless?
 *      // @me: yes! .. for god snake!
 * }
 *
 * class B extends A {
 *      // will do horseshit!
 *      private function _call() {
 *          return 'https://www.youtube.com/watch?v=_I6AAguhMsM';
 *      }
 * }
 *
 *
 *
 * @package WizeWiz\MailjetMailer\Tests
 */
class FakeClient extends Client {

    private $apikey;
    private $apisecret;
    private $apitoken;
    private $version = Config::MAIN_VERSION;
    private $url = Config::MAIN_URL;
    private $secure = Config::SECURED;
    private $call = true;
    private $settings = [];
    private $changed = false;
    private $requestOptions = [
        self::TIMEOUT => 15,
        self::CONNECT_TIMEOUT => 2,
    ];
    private $smsResources = [
        'send',
        'sms',
        'sms-send'
    ];
    private $dataAction = [
        'csverror/text:csv',
        'csvdata/text:plain',
        'JSONError/application:json/LAST'
    ];

    /**
     * Set auth
     * @param string $key
     * @param string|null $secret
     * @param bool $call
     * @param array $settings
     */
    private function setAuthentication($key, $secret, $call, $settings) {
        $isBasicAuth = $this->isBasicAuthentication($key, $secret);
        if ($isBasicAuth) {
            $this->apikey = $key;
            $this->apisecret = $secret;
        } else {
            $this->apitoken = $key;
            $this->version = Config::SMS_VERSION;
        }
        $this->initSettings($call, $settings);
        $this->setSettings();
    }

    /**
     * Magic method to call a mailjet resource
     * @param string $method Http method
     * @param string $resource mailjet resource
     * @param string $action mailjet resource action
     * @param array $args Request arguments
     * @return Response server response
     */
    private function _call($method, $resource, $action, $args) {
        $args = array_merge([
            'id' => '',
            'actionid' => '',
            'filters' => [],
            'body' => $method == 'GET' ? null : '{}',
        ], array_change_key_case($args));

        $url = $this->buildURL($resource, $action, $args['id'], $args['actionid']);

        $contentType = ($action == 'csvdata/text:plain' || $action == 'csverror/text:csv') ? 'text/plain' : 'application/json';

        $isBasicAuth = $this->isBasicAuthentication($this->apikey, $this->apisecret);
        $auth = $isBasicAuth ? [
            $this->apikey,
            $this->apisecret
        ] : [$this->apitoken];

        $request = new Request($auth, $method, $url, $args['filters'], $args['body'], $contentType, $this->requestOptions);
        return $request->call($this->call);
    }

    /**
     * Build the base API url depending on wether user need a secure connection
     * or not
     * @return string the API url;
     */
    private function getApiUrl() {
        $h = $this->secure === true ? 'https' : 'http';
        return sprintf('%s://%s/%s/', $h, $this->url, $this->version);
    }

    /**
     * Checks that both parameters are strings, which means
     * that basic authentication will be required
     *
     * @param string $key
     * @param string $secret
     *
     * @return boolean flag
     */
    private function isBasicAuthentication($key, $secret) {
        if (!empty($key) && !empty($secret)) {
            return true;
        }
        return false;
    }

    /**
     * Build the final call url without query strings
     * @param string $resource Mailjet resource
     * @param string $action Mailjet resource action
     * @param string $id mailjet resource id
     * @param string $actionid mailjet resource actionid
     * @return string final call url
     */
    private function buildURL($resource, $action, $id, $actionid) {
        $path = 'REST';
        if (in_array($resource, $this->smsResources)) {
            $path = '';
        } elseif (in_array($action, $this->dataAction)) {
            $path = 'DATA';
        }

        $arrayFilter = [
            $path,
            $resource,
            $id,
            $action,
            $actionid
        ];
        return $this->getApiUrl() . join('/', array_filter($arrayFilter));
    }

    // TODO : make the next code more readable
    // TODO : good point!

    /**
     * Temporary set the variables generating the url
     * @param array $options contain temporary modifications for the client
     * @param array $resource may contain the version linked to the ressource
     */
    private function setOptions($options, $resource) {
        if (!empty($options['version']) && is_string($options['version'])) {
            $this->version = $options['version'];
        } elseif (!empty($resource[2])) {
            $this->version = $resource[2];
        }

        if (!empty($options['url']) && is_string($options['url'])) {
            $this->url = $options['url'];
        }

        if (isset($options['secured']) && is_bool($options['secured'])) {
            $this->secure = $options['secured'];
        }

        if (isset($options['call']) && is_bool($options['call'])) {
            $this->call = $options['call'];
        }
        $this->changed = true;
    }


    /**
     * set back the variables generating the url
     */
    private function setSettings() {
        if (!empty($this->settings['url']) && is_string($this->settings['url'])) {
            $this->url = $this->settings['url'];
        }
        if (!empty($this->settings['version']) && is_string($this->settings['version'])) {
            $this->version = $this->settings['version'];
        }
        if (isset($this->settings['call']) && is_bool($this->settings['call'])) {
            $this->call = $this->settings['call'];
        }
        if (isset($this->settings['secured']) && is_bool($this->settings['secured'])) {
            $this->secure = $this->settings['secured'];
        }
        $this->changed = false;
    }

    /**
     * Set a backup if the variables generating the url are change during a call.
     */
    private function initSettings($call, $settings = []) {
        if (!empty($settings['url']) && is_string($settings['url'])) {
            $this->settings['url'] = $settings['url'];
        } else {
            $this->settings['url'] = $this->url;
        }

        if (!empty($settings['version']) && is_string($settings['version'])) {
            $this->settings['version'] = $settings['version'];
        } else {
            $this->settings['version'] = $this->version;
        }

        $settings['call'] = $call;
        if (isset($settings['call']) && is_bool($settings['call'])) {
            $this->settings['call'] = $settings['call'];
        } else {
            $this->settings['call'] = $this->call;
        }

        if (isset($settings['secured']) && is_bool($settings['secured'])) {
            $this->settings['secured'] = $settings['secured'];
        } else {
            $this->settings['secured'] = $this->secure;
        }

        $this->changed = false;
    }

    protected function do($method, $resource, $args, $options) {
        if (!empty($options)) {
            $this->setOptions($options, $resource);
        }
        // whatever resource[0] and $resource[1] are??
        $result = $this->_call('POST', $resource[0], $resource[1], $args);

        if (!empty($this->changed)) {
            $this->setSettings();
        }
        return $result;
    }

    /**
     * Trigger a POST request
     * @param array $resource Mailjet Resource/Action pair
     * @param array $args Request arguments
     * @return Response
     */
    public function post($resource, array $args = [], array $options = []) {
        return $this->do('POST', $resource, $args, $options);
    }

    /**
     * Trigger a GET request
     * @param array $resource Mailjet Resource/Action pair
     * @param array $args Request arguments
     * @return Response
     */
    public function get($resource, array $args = [], array $options = []) {
        return $this->do('GET', $resource, $args, $options);
    }

    /**
     * Trigger a POST request
     * @param array $resource Mailjet Resource/Action pair
     * @param array $args Request arguments
     * @return Response
     */
    public function put($resource, array $args = [], array $options = []) {
        return $this->do('PUT', $resource, $args, $options);
    }

    /**
     * Trigger a GET request
     * @param array $resource Mailjet Resource/Action pair
     * @param array $args Request arguments
     * @return Response
     */
    public function delete($resource, array $args = [], array $options = []) {
        return $this->do('DELETE', $resource, $args, $options);

    }
}