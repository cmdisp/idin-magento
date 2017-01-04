<?php
 
/* @var $installer CMGroep_Idin_Model_Entity_Setup */
$installer = $this;

$installer->startSetup();
$installer->addCustomerAttributes();
$installer->addProductAttributes();
$installer->endSetup();