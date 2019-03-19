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
    const STATE_VERIFIED = 'verified';
    const STATE_USER_NOT_FOUND = 'userNotFound';
    const STATE_UNVERIFIED_EMAIL = 'unverifiedEmail';
    const STATE_CANNOT_VERIFY = 'cannotVerify';
    const SETTING_DOT_REPLACEMENT = '--';

    /** @var modX */
    public $modx = null;

    /** @var string  */
    public $namespace = 'twilio';

    /** @var array */
    public $options = [];

    /** @var \Twilio\SDK\Twilio  */
    protected $api = null;

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
     * Create an Twilio & Management instance
     */
    public function init()
    {
        try {
            $config = [
                'domain' => $this->getSystemSetting('domain', ''),
                'client_id' => $this->getSystemSetting('client_id', ''),
                'client_secret' => $this->getSystemSetting('client_secret', ''),
                'redirect_uri' => $this->getSystemSetting('redirect_uri', ''),
                'audience' => $this->getSystemSetting('audience', ''),
                'scope' => $this->getSystemSetting('scope', 'openid profile email address phone'),
                'persist_id_token' => $this->getSystemSetting('persist_id_token', false),
                'persist_access_token' => $this->getSystemSetting('persist_access_token', true),
                'persist_refresh_token' => $this->getSystemSetting('persist_refresh_token', false),
            ];

            $this->api = new Twilio\SDK\Twilio($config);

            $this->authApi = new Twilio\SDK\API\Authentication($config['domain'], $config['client_id'], $config['client_secret']);
            $credentials = $this->authApi->client_credentials([
                'audience' => 'https://' . $config['domain'] . '/api/v2/',
                'scope' => 'read:users read:users_app_metadata update:users update:users_app_metadata',
            ]);
            $this->managementApi = new Twilio\SDK\API\Management($credentials['access_token'], $config['domain']);

        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
        }

        if (!$this->api instanceof Twilio\SDK\Twilio || !$this->managementApi instanceof Twilio\SDK\API\Management) {

            $this->modx->log(modX::LOG_LEVEL_ERROR, '[Twilio] could not load Twilio\SDK\Twilio!');
            return false;

        }

        return true;
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
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
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
