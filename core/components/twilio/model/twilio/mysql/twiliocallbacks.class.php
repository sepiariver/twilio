<?php
/**
 * @package twilio
 */
require_once (strtr(realpath(dirname(dirname(__FILE__))), '\\', '/') . '/twiliocallbacks.class.php');
class TwilioCallbacks_mysql extends TwilioCallbacks {}
?>