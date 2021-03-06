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

class CMGroep_Idin_Block_Checkout_Thirdparty_Abstract extends Mage_Core_Block_Template
{
    /** @var CMGroep_Idin_Helper_Data */
    protected $_helper = null;

    /**
     * @return CMGroep_Idin_Helper_Data
     */
    public function getHelper()
    {
        if ($this->_helper == null) {
            $this->_helper = Mage::helper('cmgroep_idin');
        }

        return $this->_helper;
    }

    /**
     * Generates Issuer Select HTML
     *
     * @return string
     */
    public function getIssuerSelectHtml()
    {
        return $this->getLayout()->createBlock('cmgroep_idin/core_issuer_select')->toHtml();
    }

    /**
     * Determines if verification is required
     *
     * @return bool
     */
    public function isAgeVerificationRequired()
    {
        return $this->getHelper()->ageVerificationRequired();
    }

    /**
     * Get url for starting age verification from the checkout
     *
     * @return string
     */
    public function getVerifyAgeUrl()
    {
        return Mage::getUrl('idin/auth/verifyAge', array('mode' => 'checkout'));
    }

    /**
     * Only render when age verification is active
     *
     * @return string
     */
    public function _toHtml()
    {
        if (Mage::helper('cmgroep_idin')->getIdinAgeVerificationActive()
            && Mage::helper('cmgroep_idin')->ageVerificationRequired(true)
            && Mage::helper('cmgroep_idin')->getExtensionActive()) {
            return parent::_toHtml();
        }
    }
}