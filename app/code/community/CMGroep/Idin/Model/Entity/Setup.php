<?php
/**
 * MIT License
 *
 * Copyright (c) 2017 CM Groep
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @category   CMGroep
 * @package    Idin
 * @author     Epartment Ecommerce B.V. <support@epartment.nl>
 * @copyright  2016-2017 CM Groep
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

class CMGroep_Idin_Model_Entity_Setup extends Mage_Eav_Model_Entity_Setup
{
    public function addCustomerAttributes()
    {
        /**
         * Retrieve Customer Entity Defaults
         */
        $entityTypeId = $this->getEntityTypeId('customer');
        $defaultAttributeSetId = $this->getDefaultAttributeSetId($entityTypeId);
        $defaultAttributeGroupId = $this->getDefaultAttributeGroupId($entityTypeId, $defaultAttributeSetId);

        /**
         * Add iDIN attributes to Customer Entity
         */
        $this->addAttribute($entityTypeId, 'idin_bin', array(
            'type' => 'varchar',
            'label' => 'iDIN Bin Token',
            'required' => false,
            'visible' => false,
            'user_defined' => 0
        ));

        $this->addAttribute($entityTypeId, 'idin_age_verified', array(
            'type' => 'int',
            'label' => 'iDIN Age Verified',
            'required' => false,
            'visible' => false,
            'user_defined' => 0
        ));

        $this->addAttributeToGroup($entityTypeId, $defaultAttributeSetId, $defaultAttributeGroupId, 'idin_bin');
    }

    public function addProductAttributes()
    {
        $entityTypeId = $this->getEntityTypeId(Mage_Catalog_Model_Product::ENTITY);

        $this->addAttribute($entityTypeId, 'idin_require_age_verification', array(
            'group' => Mage::helper('cmgroep_idin')->__('iDIN'),
            'label' => Mage::helper('cmgroep_idin')->__('18+ Product'),
            'type' => 'int',
            'input' => 'boolean',
            'source_model' => 'eav/entity_attribute_source_boolean',
            'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'default' => 0
        ));
    }
}