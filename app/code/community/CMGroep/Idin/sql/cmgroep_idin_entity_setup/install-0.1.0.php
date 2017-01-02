<?php
 
/* @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;

$installer->startSetup();

/**
 * Retrieve Customer Entity Defaults
 */
$entityTypeId = $installer->getEntityTypeId('customer');
$defaultAttributeSetId = $installer->getDefaultAttributeSetId($entityTypeId);
$defaultAttributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $defaultAttributeSetId);


/**
 * Add iDIN attributes to Customer Entity
 */
$installer->addAttribute($entityTypeId, 'idin_bin', array(
    'type' => 'varchar',
    'label' => 'iDIN Bin Token',
    'required' => false,
    'visible' => false,
    'user_defined' => 0
));

$installer->addAttribute($entityTypeId, 'idin_age_verified', array(
    'type' => 'int',
    'label' => 'iDIN Age Verified',
    'required' => false,
    'visible' => false,
    'user_defined' => 0
));

$installer->addAttributeToGroup($entityTypeId, $defaultAttributeSetId, $defaultAttributeGroupId, 'idin_bin');

$installer->endSetup();