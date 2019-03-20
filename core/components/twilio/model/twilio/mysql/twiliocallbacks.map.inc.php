<?php
/**
 * @package twilio
 */
$xpdo_meta_map['TwilioCallbacks']= array (
  'package' => 'twilio',
  'version' => '1.1',
  'table' => 'twilio_callbacks',
  'extends' => 'xPDOObject',
  'tableMeta' => 
  array (
    'engine' => 'InnoDB',
  ),
  'fields' => 
  array (
    'id' => NULL,
    'data' => '[]',
    'tpl' => '',
    'expires' => 0,
    'sender_id' => NULL,
  ),
  'fieldMeta' => 
  array (
    'id' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
      'phptype' => 'string',
    ),
    'data' => 
    array (
      'dbtype' => 'text',
      'null' => false,
      'phptype' => 'json',
      'default' => '[]',
    ),
    'tpl' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'null' => false,
      'phptype' => 'string',
      'default' => '',
    ),
    'expires' => 
    array (
      'dbtype' => 'int',
      'precision' => '20',
      'null' => false,
      'phptype' => 'timestamp',
      'default' => 0,
    ),
    'sender_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'attributes' => 'unsigned',
      'null' => false,
      'phptype' => 'integer',
    ),
  ),
);
