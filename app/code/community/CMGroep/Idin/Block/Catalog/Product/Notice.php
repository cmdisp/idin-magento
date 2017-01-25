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

class CMGroep_Idin_Block_Catalog_Product_Notice extends CMGroep_Idin_Block_Abstract
{
    /** @var Mage_Catalog_Model_Product */
    protected $_product = null;

    /**
     * Retrieve current product
     *
     * @return Mage_Catalog_Model_Product
     */
    private function getProduct()
    {
        if ($this->_product == null) {
            $this->_product = Mage::registry('current_product');
        }

        return $this->_product;
    }

    /**
     * Determines if product requires age verification
     *
     * @return bool
     */
    private function productRequiredAgeVerification()
    {
        $productRequiresAgeVerification = Mage::getResourceModel('catalog/product')->getAttributeRawValue($this->getProduct()->getId(), 'idin_require_age_verification', Mage::app()->getStore()->getId());

        return $productRequiresAgeVerification == 1;
    }

    /**
     * Only render when 18+ attribute is set on the product
     *
     * @return string
     */
    public function _toHtml()
    {
        /**
         * Determine if notice should be visible based on the settings
         */
        $showProductNoticeSetting = $this->getHelper()->getIdinAgeVerificationProductNoticeSetting();

        if ($this->getHelper()->getExtensionActive() && $this->getHelper()->getIdinAgeVerificationActive()) {
            if ($showProductNoticeSetting == CMGroep_Idin_Model_System_Config_Source_Showproductnotice::MODE_ALWAYS) {
                return parent::_toHtml();
            } else if ($showProductNoticeSetting == CMGroep_Idin_Model_System_Config_Source_Showproductnotice::MODE_PRODUCTS) {
                if ($this->productRequiredAgeVerification()) {
                    return parent::_toHtml();
                }
            }
        }
    }

    /**
     * Retrieve product notice from settings
     *
     * @return string
     */
    public function getProductNotice()
    {
        return $this->getHelper()->getIdinAgeProductVerificationNotice();
    }
}