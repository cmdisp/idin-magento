<?php
 
/* @var $installer CMGroep_Idin_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$installer->createPendingRegistrationsTable();
$installer->endSetup();