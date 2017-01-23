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
class CMGroep_Idin_Model_Adminhtml_Observer
{

    /**
     * If the module is de-activated, hide the iDIN 18+ Product Attribute
     *
     * @param $event
     */
    public function updateIdinProductAttribute($event)
    {
        $moduleStatus = Mage::getStoreConfig('cmgroep_idin/common/active');

        if ($moduleStatus == 0) {
            $attributeApi = Mage::getModel('catalog/product_attribute_api');
            $attributeData = array('is_visible' => false);
            $attributeApi->update('idin_require_age_verification', $attributeData);
        } else {
            $attributeApi = Mage::getModel('catalog/product_attribute_api');
            $attributeData = array('is_visible' => true);
            $attributeApi->update('idin_require_age_verification', $attributeData);
        }
    }
}