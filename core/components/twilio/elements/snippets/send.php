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
if ($hook && $hook->formit && is_array($hook->formit->config)) {
    $props = $hook->getValues();
    foreach ($hook->formit->config as $k => $v) {
        if (strpos($k, $twilio->namespace) === 0) {
            $props[substr($k, strlen($twilio->namespace . '.'))] = $v;
        }
    }
    $isFormIt = true;
} else {
    $props = $scriptProperties;
    $isFormIt = false;
}

// OPTIONS
$number = $twilio->getOption('number', $props, '');
$country = $twilio->getOption('country', $props, 'US', true);
$message = $twilio->getOption('message', $props, '');
$type = $twilio->getOption('type', $props, '');

$callbackUrl = $twilio->getOption('callbackUrl', $props, '');
$callbackFields = $twilio->explodeAndClean($twilio->getOption('callbackFields', $props, ''));
$callbackGetParam = $twilio->getOption('callbackGetParam', $props, 'cbid', true);

$callbackTpl = $twilio->getOption('callbackTpl', $props, '');
$callbackLinkTpl = $twilio->getOption('callbackLinkTpl', $props, '@INLINE [[+callback_link]]');
$errorTpl = $twilio->getOption('errorTpl', $props, '@INLINE Error sending SMS.');
$successTpl = $twilio->getOption('successTpl', $props, 'twilio.sent_result');

$successPlaceholder = $twilio->getOption('successPlaceholder', $props, 'twilio_output');
$debug = $twilio->getOption('debug', $props, '');

$options = [
    'countryCode' => $country,
    'type' => $type,
];
$phone_number = $twilio->lookup($number, $options);

if (!empty($phone_number['phoneNumber'])) {
    if (filter_var($callbackUrl, FILTER_VALIDATE_URL) !== false) {
        $callbackData = [];
        foreach ($callbackFields as $field) {
            $callbackData[$field] = $twilio->getOption($field, $props, '');
        }
        $callback = $twilio->createCallback($callbackData, $callbackTpl, $modx->user->id);
        if ($callback) {
            parse_str(parse_url($callbackUrl, PHP_URL_QUERY), $query);
            $query[$callbackGetParam] = $callback->id;
            $callbackUrl .= '?' . http_build_query($query);
            $message .= PHP_EOL . $twilio->getChunk($callbackLinkTpl, [
                'callback_link' => $callbackUrl,
            ]);
        }

    }
    $sent = $twilio->send($phone_number['phoneNumber'], $message);
}

if (!empty($debug)) {
    $output = $twilio->debug([
        'debug' => $debug,
        'result' => $sent,
    ]);
    if ($isFormIt) {
        $hook->addError('twilio', $output);
        return false;
    } else {
        return $output;
    }
}

if (empty($sent)) {
    $output = $twilio->getChunk($errorTpl, $props);
    if ($isFormIt) {
        $hook->addError('twilio', $output);
        return false;
    } else {
        return $output;
    }
}

$output = $twilio->getChunk($successTpl, $sent);
if ($isFormIt) {
    $modx->setPlaceholder($successPlaceholder, $output);
    return true;
} else {
    return $output;
}