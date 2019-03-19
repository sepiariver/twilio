<?php
/**
 * twilio.loggedIn
 *
 * Check user login state and show content or redirect accordingly
 *
 * OPTIONS:
 * &forceLogin -    (bool) Enable/disable forwarding to Twilio for login if anonymous. &anonymousTpl will not be displayed if this is true. Default true
 * &loggedInTpl -   (string) Chunk TPL to render when logged in. Default '@INLINE ...'
 * &twilioUserTpl -  (string) Chunk TPL to render when logged into Twilio but not MODX. Default '@INLINE ...'
 * &anonymousTpl -  (string) Chunk TPL to render when not logged in. Default '@INLINE ...'
 * &debug -         (bool) Enable debug output. Default false
 *
 * @var modX $modx
 * @var array $scriptProperties
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

$forceLogin = $modx->getOption('forceLogin', $scriptProperties, true);
$loggedInTpl = $modx->getOption('loggedInTpl', $scriptProperties, '@INLINE You\'re logged in.');
$twilioUserTpl = $modx->getOption('twilioUserTpl', $scriptProperties, '@INLINE Your Twilio user isn\'t valid here. Try logging in again.');
$anonymousTpl = $modx->getOption('anonymousTpl', $scriptProperties, '@INLINE Login required.');
$debug = $modx->getOption('debug', $scriptProperties, '');

// Expose properties for TPL
$props = $scriptProperties;

$corePath = $modx->getOption('twilio.core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/twilio/');
/** @var Twilio $twilio */
$twilio = $modx->getService('twilio', 'Twilio', $corePath . 'model/twilio/', ['core_path' => $corePath]);

if (!($twilio instanceof Twilio) || !$twilio->init()) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[twilio.loggedIn] could not load the required class on line: ' . __LINE__);

    // MODX session is the record of truth for logged-in state
    if ($modx->user && $modx->user->hasSessionContext($modx->context->key)) {
        return $modx->getChunk($loggedInTpl, $props);
    } else {
        $modx->sendUnauthorizedPage();
        return;
    }
}

// Call for userinfo
$userInfo = $twilio->getUser($forceLogin);
if ($userInfo !== false) {
    $props = array_merge($props, $userInfo);
}

// Debug info
if ($debug) {
    $props['caller'] = 'twilio.loggedIn';
    $props['context_key'] = $modx->context->key;
    if ($modx->resource) $props['resource_id'] = $modx->resource->id;
}

// Check for session
if ($modx->user->hasSessionContext($modx->context->key)) {
    if ($debug) {
        return $twilio->debug($props);
    }
    // MODX session is the record of truth for logged-in state
    return $twilio->getChunk($loggedInTpl, $props);
} else {
    if ($userInfo !== false) {
        if ($debug) {
            return $twilio->debug($props);
        }
        // User logged-in to Twilio but not MODX;
        return $twilio->getChunk($twilioUserTpl, $props);
    } else {
        if ($debug) {
            return $twilio->debug($props);
        }
        // User not logged-in to Twilio
        return $twilio->getChunk($anonymousTpl, $props);
    }
}
