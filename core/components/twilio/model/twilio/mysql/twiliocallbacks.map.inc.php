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
    'expires' => 'CURRENT_TIMESTAMP',
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
      'dbtype' => 'timestamp',
      'null' => false,
      'phptype' => 'timestamp',
      'default' => 'CURRENT_TIMESTAMP',
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
  'indexes' => 
  array (
    'PRIMARY' => 
    array (
      'alias' => 'PRIMARY',
      'primary' => true,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'id' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
);
