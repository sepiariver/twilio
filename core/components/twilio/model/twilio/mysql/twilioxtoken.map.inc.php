<?php
/**
 * @package twilio
 */
$xpdo_meta_map['TwilioXToken']= array (
  'package' => 'twilio',
  'version' => '1.1',
  'table' => 'twilio_x_tokens',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' => 
  array (
    'engine' => 'InnoDB',
  ),
  'fields' => 
  array (
    'x_token' => NULL,
    'timestamp' => 'CURRENT_TIMESTAMP',
    'expires' => NULL,
  ),
  'fieldMeta' => 
  array (
    'x_token' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '2000',
      'phptype' => 'string',
    ),
    'timestamp' => 
    array (
      'dbtype' => 'timestamp',
      'phptype' => 'timestamp',
      'null' => false,
      'default' => 'CURRENT_TIMESTAMP',
    ),
    'expires' => 
    array (
      'dbtype' => 'timestamp',
      'phptype' => 'timestamp',
      'null' => true,
    ),
  ),
);
