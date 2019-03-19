<?php
/**
 * Twilio.JWTLogin
 *
 * Decodes a JWT to login a user.
 *
 * OPTIONS:
 *
 * @package Twilio
 * @author @sepiariver <info@sepiariver.com>
 * Copyright 2018 by YJ Tso
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

// OPTIONS
$loginContexts = $modx->getOption('loginContexts', $scriptProperties, '');
$continueTwilio = $modx->getOption('continueTwilio', $scriptProperties, true);
$errorTpl = $modx->getOption('errorTpl', $scriptProperties, '@INLINE Error logging in.');
$successTpl = $modx->getOption('successTpl', $scriptProperties, '@INLINE Success.');
$start = time();

$corePath = $modx->getOption('twilio.core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/twilio/');
/** @var Twilio $twilio */
$twilio = $modx->getService('twilio', 'Twilio', $corePath . 'model/twilio/', ['core_path' => $corePath]);

if (!($twilio instanceof Twilio) || !$twilio->init()) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[twilio.JWTLogin] could not load the required class on line: ' . __LINE__);
    return $modx->getChunk($errorTpl, ['start' => $start, 'end' => time(), 'msg' => 'Unknown error. Please contact site administrator.']);
}

// Required
$jwt = $modx->getOption('token', $_GET, '');
$state = $modx->getOption('state', $_GET, '');
if (empty($jwt) || empty($state)) {
    $modx->log(modX::LOG_LEVEL_WARN, '[Twilio.JWTLogin] missing required argument.');
    return $twilio->getChunk($errorTpl, ['start' => $start, 'end' => time(), 'msg' => 'Missing parameter.']);
}

// Get User from JWT
$user = $twilio->getUserFromJWT($jwt);
if (!$user) {
    $modx->log(modX::LOG_LEVEL_INFO, '[Twilio.JWTLogin] error logging in.');
    return $twilio->getChunk($errorTpl, ['start' => $start, 'end' => time(), 'msg' => 'Error logging in on line: ' . __LINE__]);
}

// Add current context
$loginContexts = $modx->context->key . ',' . $loginContexts;
$loginContexts = $twilio->explodeAndClean($loginContexts);

// Verify and login
$result = false;
if ($twilio->verifyUser() === Twilio::STATE_VERIFIED) {
    $result = $twilio->login($loginContexts, false, false);
}
if (!$result) {
    $modx->log(modX::LOG_LEVEL_INFO, '[Twilio.JWTLogin] error logging in.');
    return $twilio->getChunk($errorTpl, ['start' => $start, 'end' => time(), 'msg' => 'Error logging in on line: ' . __LINE__]);
} else {
    if ($continueTwilio) {
        $modx->sendRedirect('https://' . $twilio->getOption('domain') . '/continue?state=' . $state);
    } else {
        return $twilio->getChunk($successTpl);
    }
}
