<?php
/**
 * @package twilio
 */
require_once (strtr(realpath(dirname(dirname(__FILE__))), '\\', '/') . '/twilioxtoken.class.php');
class TwilioXToken_mysql extends TwilioXToken {}
?>