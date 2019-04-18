<?php
/**
 * Twilio class for MODX.
 * @package Twilio
 *
 * @author @sepiariver <info@sepiariver.com>
 * Copyright 2017 by YJ Tso
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 **/

class Twilio
{

    /** @var modX */
    public $modx = null;

    /** @var string  */
    public $namespace = 'twilio';

    /** @var array */
    public $options = [];

    /** @var \Twilio\Rest\Client  */
    protected $client = null;

    public function __construct(modX &$modx, array $options = array())
    {
        $this->modx =& $modx;

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/twilio/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH) . 'components/twilio/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/twilio/');
        $dbPrefix = $this->getOption('table_prefix', $options, $this->modx->getOption('table_prefix', null, 'modx_'));

        /* load config defaults */
        $this->options = array_merge(array(
            'namespace' => $this->namespace,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'vendorPath' => $corePath . 'model/vendor/',
            'processorsPath' => $corePath . 'processors/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'templatesPath' => $corePath . 'templates/',
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'connectorUrl' => $assetsUrl . 'connector.php',
            'callbackIdLength' => 32,
            'callbackDefaultExpires' => time() + (3 * 24 * 60 * 60), // 3 days
        ), $options);

        $this->modx->addPackage('twilio', $this->options['modelPath'], $dbPrefix);
        $this->modx->lexicon->load('twilio:default');

        require_once($this->options['vendorPath'] . 'autoload.php');
    }

    /**
     * Create a Twilio Client instance
     * Overrides any previously created instances
     */
    public function init()
    {
        $sid = $this->getOption('account_sid');
        $token = $this->getOption('auth_token');

        try {
            $this->client = new Twilio\Rest\Client($sid, $token);
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
        }

        if (!$this->client instanceof Twilio\Rest\Client) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[Twilio] could not load Twilio\Rest\Client!');
            return false;
        }

        return true;
    }

    /**
     * Lookup a phone number using Twilio REST API
     *
     * @param string $phoneNumber   Phone number to lookup
     * @param array $options        Array of options
     * @param bool $save            Not yet implemented
     *
     * @return array|null           Result from API lookup
     */
    public function lookup(string $phoneNumber = '', array $options = [], $save = false)
    {
        if (empty($phoneNumber)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio.lookup requires phoneNumber.');
            return null;
        }
        // Default
        if (empty($options)) {
            $options = ['countryCode' => 'US'];
        }
        // Allowed Types
        if (!empty($options['type']) && !in_array($options['type'], ['carrier', 'caller-name'])) {
            unset($options['type']);
        }

        try {
            $result = $this->client->lookups->v1->phoneNumbers($phoneNumber)->fetch($options)->toArray();
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
            return null;
        }

        // @TODO
        if ($save) {

        }
        return $result;

    }

    /**
     * Send SMS using Twilio
     *
     * @param string $phoneNumber   Phone number recipient
     * @param string $message       Message body
     * @param bool $save            Not yet implemented
     * @param string $from          From number override
     *
     * @return array|null           Result from send
     */
    public function send(string $phoneNumber = '', string $message = '', $save = false, string $from = '')
    {
        if (empty($phoneNumber) || empty($message)) {
            return null;
        }

        try {
            $result = $this->client->messages->create($phoneNumber, [
                'from' => (empty($from)) ? $this->getOption('sms_sender') : $from,
                'body' => $message,
            ])->toArray();
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
            return null;
        }

        // @TODO
        if ($save) {

        }
        return $result;
    }

    /**
     * Create a callback
     *
     * @param array $data           Array of data for callback rendering
     * @param string $tpl           TPL Chunk name or @INLINE for $this->getChunk()
     * @param modUser|int $user     User creating callback.
     *
     * @return TwilioCallbacks|null Created callback object or null.
     */
    public function createCallback(array $data = [], string $tpl = '', $user, $expires = null)
    {
        if (is_numeric($user)) {
            $user_id = (int) abs($user);
        } elseif ($user instanceof modUser) {
            $user_id = (int) $user->id;
        } elseif ($this->modx->user instanceof modUser) {
            $user_id = (int) $this->modx->user->id;
        } else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: Invalid user to create callback.');
            return null;
        }
        if (version_compare(PHP_VERSION, '7.0.0') < 0) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: create callback requires PHP 7.');
            return null;
        }
        if ($expires === null) {
            $expires = $this->options['callbackDefaultExpires'];
        }

        $obj = $this->modx->newObject('TwilioCallbacks', [
            'id' => bin2hex(random_bytes($this->options['callbackIdLength'])),
            'data' => $data,
            'tpl' => $tpl,
            'expires' => $expires,
            'sender_id' => $user_id,
        ]);
        if (!$obj->save()) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: Failed to create callback.');
            return null;
        }
        return $obj;
    }

    /**
     * Get a callback
     *
     * @param string $id        ID of callback to retrieve
     * @param string $tpl       Override TPL passed to $this->getChunk()
     * @param bool $render      Flag to render or return object
     * @param bool $invalidate  Flag to invalidate callback after retrieval
     *
     * @return TwilioCallbacks|null|string  Result based on render flag.
     */
    public function getCallback(string $id, string $tpl = '', $render = true, $invalidate = true)
    {
        if (empty($id)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: Missing callback ID.');
            return ($render) ? '' : null;
        }
        $c = $this->modx->newQuery('TwilioCallbacks');
        $c->where([
            'id' => $id,
            'expires:>' => strftime('%F %T'),
        ]);
        $obj = $this->modx->getObject('TwilioCallbacks', $c);
        if (!$obj || !($obj instanceof TwilioCallbacks)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Twilio: No callback found for ID ' . $id);
            return ($render) ? '' : null;
        }
        $invalidated = false;
        if ($invalidate) {
            $obj->set('expires', 1);
            $invalidated = $obj->save();
        }
        if ($render) {
            $data = $obj->get('data');
            if (!is_array($data)) $data = [];
            $data['invalidated'] = $invalidated;
            if (!empty($tpl)) {
                return $this->getChunk($tpl, $data);
            } elseif (!empty($obj->get('tpl'))) {
                return $this->getChunk($obj->get('tpl'), $data);
            } else {
                return '';
            }
        }
        return $obj;

    }

    /**
     * Invalidate a callback
     *
     * @param string $id            ID of callback to invalidate
     *
     * @return bool|TwilioCallbacks Success or fail or object
     */
    public function invalidateCallback(string $id, bool $returnObj = false)
    {
        if (empty($id)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: Missing callback ID to invalidate.');
            return false;
        }
        $obj = $this->modx->getObject('TwilioCallbacks', ['id' => $id]);
        if (!$obj || !($obj instanceof TwilioCallbacks)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: No callback found for ID ' . $id);
            return false;
        }
        $obj->set('expires', 1);
        if (!$obj->save()) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: Failed to invalidate callback ID ' . $id);
            return false;
        }
        return ($returnObj) ? $obj : true;
    }

    /**
     * Debugging
     * Profoundly flawed but mostly works
     *
     * @param array $properties
     * @return string|void
     */
    public function debug($properties = [])
    {
        $debugInfo = (is_array($properties)) ? print_r($properties, true) : 'Twilio unknown error on line: ' . __LINE__;
        if ($properties['debug'] === 'log') {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $debugInfo);
            return;
        }
        if ($properties['debug'] === 'print') {
            return "<pre>{$debugInfo}</pre>";
        }
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */

    public function getOption($key = '', $options = [], $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if (is_array($options) && array_key_exists($key, $options)) {
                // Simple array access
                $option = $options[$key];
            } elseif (is_array($options) && array_key_exists("{$this->namespace}.{$key}", $options)) {
                // Namespaced properties like formit->config
                $option = $options["{$this->namespace}.{$key}"];
            } elseif (array_key_exists($key, $this->options)) {
                // Instance config
                $option = $this->options[$key];
            } elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
                // System settings
                $option = $this->modx->getOption("{$this->namespace}.{$key}");
            }
        }
        return $option;
    }

    /**
     * Get a namespaced system setting directly from the modSystemSetting table.
     * Does not allow cascading Context, User Group, nor User settings, like the name suggests.
     *
     * @param string $key The option key to search for.
     * @param mixed $default The default value returned if the option is not found as a
     * namespaced system setting; by default this value is ''.
     * @return mixed The option value or the default value specified.
     */
    protected function getSystemSetting($key = '', $default = '')
    {
        if (empty($key)) return $default;
        $query = $this->modx->newQuery('modSystemSetting', [
            'key' => "{$this->namespace}.{$key}",
        ]);
        $query->select('value');
        $value = $this->modx->getValue($query->prepare());
        if ($value === false || $value === null) $value = $default;
        return $value;
    }

    /**
     * Transforms a string to an array with removing duplicates and empty values
     *
     * @param $string
     * @param string $delimiter
     * @return array
     */
    public function explodeAndClean($string, $delimiter = ',')
    {
        $string = (string) $string;
        $array = explode($delimiter, $string);    // Explode fields to array
        $array = array_map('trim', $array);       // Trim array's values
        $array = array_keys(array_flip($array));  // Remove duplicate fields
        $array = array_filter($array);            // Remove empty values from array

        return $array;
    }

    /**
     * Processes a chunk or given string
     *
     * @param string $tpl
     * @param array $phs
     * @return string
     */
    public function getChunk($tpl = '', $phs = [])
    {
        if (empty($tpl)) return '';
        if (!is_array($phs)) $phs = [];
        if (strpos($tpl, '@INLINE ') !== false) {
            $content = str_replace('@INLINE', '', $tpl);
            /** @var \modChunk $chunk */
            $chunk = $this->modx->newObject('modChunk', array('name' => 'inline-' . uniqid()));
            $chunk->setCacheable(false);

            return $chunk->process($phs, $content);
        }
        // Not strictly necessary but helpful in common error scenario
        if ($this->modx->getCount('modChunk', ['name' => $tpl]) !== 1) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: no Chunk with name ' . $tpl);
            return '';
        }
        return $this->modx->getChunk($tpl, $phs);
    }

}
