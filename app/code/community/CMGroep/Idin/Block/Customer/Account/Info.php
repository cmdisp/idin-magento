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

class CMGroep_Idin_Block_Customer_Account_Info extends CMGroep_Idin_Block_Abstract
{
    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer;

    /**
     * Gets the current customer
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        if ($this->_customer == null) {
            $this->_customer = Mage::helper('customer')->getCurrentCustomer();
        }

        return $this->_customer;
    }

    /**
     * @return bool
     */
    public function shouldShow()
    {
        if (($this->getHelper()->getIdinLoginActive() || $this->getHelper()->getIdinAgeVerificationActive()) && $this->getHelper()->getExtensionActive()) {
            return true;
        }

        return false;
    }

    /**
     * Determines if customer has iDIN login enabled
     *
     * @return bool
     */
    public function customerHasIdin()
    {
        return empty($this->getCustomer()->getIdinBin()) === false;
    }

    /**
     * Determines if customer's age is verified
     *
     * @return bool
     */
    public function customerHasVerifiedAge()
    {
        return $this->getCustomer()->getIdinAgeVerified() == 1;
    }

    /**
     * Determines if iDIN login is enabled
     *
     * @return bool
     */
    public function isIdinLoginEnabled()
    {
        return $this->getHelper()->getIdinLoginActive();
    }

    /**
     * Determines if iDIN age verification is active
     *
     * @return bool
     */
    public function isIdinAgeVerificationEnabled()
    {
        return $this->getHelper()->getIdinAgeVerificationActive();
    }

    /**
     * Get URL for connecting iDIN to account
     *
     * @return string
     */
    public function getConnectWithIdinUrl()
    {
        return $this->getUrl('idin/auth/connect');
    }

    /**
     * Get URL for verifying age through iDIN
     *
     * @return string
     */
    public function getVerifyAgeWithIdinUrl()
    {
        return $this->getUrl('idin/auth/verifyAge');
    }
}