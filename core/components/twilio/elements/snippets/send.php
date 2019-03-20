<?php
/**
 * twilio.send
 *
 * Send SMS
 *
 * OPTIONS:
 * &debug (string) print|log    Enable debug output. Default ''
 *
 * @var modX $modx
 * @var array $props
 *
 * @package Twilio
 * @author @sepiariver <info@sepiariver.com>
 * Copyright 2019 by YJ Tso
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

$corePath = $modx->getOption('twilio.core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/twilio/');
/** @var Twilio $twilio */
$twilio = $modx->getService('twilio', 'Twilio', $corePath . 'model/twilio/', ['core_path' => $corePath]);

if (!($twilio instanceof Twilio) || !$twilio->init()) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[twilio.lookup] could not load the required class on line: ' . __LINE__);
    return;
}

/** @var Sterc\FormIt\Hook */
$formitConfig = ($hook && $hook->formit && is_array($hook->formit->config)) ? $hook->formit->config : [];
$props = array_merge($formitConfig, $scriptProperties);

// OPTIONS
$number = $modx->getOption('number', $props, '');
$country = $modx->getOption('country', $props, 'US', true);
$type = $modx->getOption('type', $props, '');
$errorTpl = $modx->getOption('errorTpl', $props, '@INLINE Error looking up number.');
$successTpl = $modx->getOption('successTpl', $props, 'twilio.lookup_result');
$debug = $modx->getOption('debug', $props, '');

if (empty($number)) return $twilio->getChunk($errorTpl, $props);

$options = [
    'countryCode' => $country,
    'type' => $type,
];
$phone_number = $twilio->lookup($number, $options);

if (!empty($phone_number['phoneNumber'])) {
    $sent = $twilio->send($phone_number['phoneNumber'], $message);
}

if (!empty($debug)) {
    return $twilio->debug([
        'debug' => $debug,
        'result' => $sent,
    ]);
}

return $twilio->getChunk($successTpl, $sent);