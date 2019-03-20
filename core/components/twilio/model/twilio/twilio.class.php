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
            'jwtLeeway' => 60,
            'jwtKeyMinLength' => 32,
        ), $options);

        $this->modx->addPackage('twilio', $this->options['modelPath'], $dbPrefix);
        $this->modx->lexicon->load('twilio:default');

        require_once($this->options['vendorPath'] . 'autoload.php');
    }

    /**
     * Create a Twilio Client instance
     */
    public function init()
    {
        try {
            $config = [
                'account_sid' => $this->getSystemSetting('account_sid', ''),
                'auth_token' => $this->getSystemSetting('auth_token', ''),
                'sending_phone' => $this->getSystemSetting('sending_phone', ''),
                'jwt_key' => $this->getSystemSetting('jwt_key', ''),
            ];

            $this->client = new Twilio\Rest\Client($config['account_sid'], $config['auth_token']);

        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
        }

        if (!$this->client instanceof Twilio\Rest\Client) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[Twilio] could not load Twilio\Rest\Client!');
            return false;
        }

        return true;
    }

    public function lookup(string $phoneNumber = '', array $options = [], int $save = 0)
    {
        if (empty($phoneNumber)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio.lookup requires phoneNumber.');
            return false;
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
            return false;
        }

        // @TODO
        if ($save) {

        }
        return $result;

    }

    public function send(string $phoneNumber = '', string $message = '', int $save = 0, string $from = '')
    {
        if (empty($phoneNumber) || empty($message)) {
            return false;
        }

        try {
            $result = $this->client->messages->create($phoneNumber, [
                'from' => (empty($from)) ? $this->getOption('sms_sender') : $from,
                'body' => $message,
            ])->toArray();
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
            return false;
        }

        // @TODO
        if ($save) {

        }
        return $result;
    }

    public function createCallback(array $data = [], string $tpl = '', $user)
    {
        if (empty($data) && empty($tpl)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: Missing requirement to create callback.');
            return null;
        }
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

        $obj = $this->modx->newObject('TwilioCallbacks', [
            'id' => bin2hex(random_bytes(64)),
            'data' => $data,
            'tpl' => $tpl,
            'sender_id' => $user_id,
        ]);
        if (!$obj->save()) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Twilio: Failed to create callback.');
            return null;
        }
        return $obj;
    }

    /**
     * Debugging
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
                $option = $options[$key];
            } elseif (is_array($options) && array_key_exists("{$this->namespace}.{$key}", $options)) {
                $option = $options["{$this->namespace}.{$key}"];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
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

        return $this->modx->getChunk($tpl, $phs);
    }

}
