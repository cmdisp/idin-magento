<?php
/**
 * MIT License
 *
 * Copyright (c) 2016 CM Groep
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

class CMGroep_Idin_Block_Customer_Account_Login_Idin extends CMGroep_Idin_Block_Abstract
{
    protected $_isRegistration = false;
    protected $_isCheckout = false;

    /**
     * Enable registration only mode
     *
     * @param $registration
     */
    public function setIsRegistration($registration)
    {
        $this->_isRegistration = $registration;
    }

    /**
     * Retrieves registration only mode
     *
     * @return bool
     */
    public function getIsRegistration()
    {
        return $this->_isRegistration;
    }

    /**
     * Enable checkout only mode
     *
     * @param $checkout
     */
    public function setIsCheckout($checkout)
    {
        $this->_isCheckout = $checkout;
    }

    /**
     * Retrieves checkout only mode
     *
     * @return bool
     */
    public function getIsCheckout()
    {
        return $this->_isCheckout;
    }

    /**
     * @return string URL for iDIN authentication
     */
    public function getFormAction()
    {
        return $this->getUrl('idin/auth/index');
    }

    /**
     * Returns whether registration is enabled or not
     *
     * @return bool
     */
    public function registrationEnabled()
    {
        return $this->_helper->getIdinRegistrationActive();
    }

    /**
     * Returns whether registration is enabled or not
     *
     * @return bool
     */
    public function loginEnabled()
    {
        return $this->_helper->getIdinLoginActive();
    }

    /**
     * Do not render the block unless one of the authentication
     * functions is enabled
     *
     * @return string
     */
    public function _toHtml()
    {
        if (($this->getHelper()->getIdinLoginActive() || $this->getHelper()->getIdinRegistrationActive()) && $this->getHelper()->getExtensionActive()) {

            /**
             * Do not render if registration is disabled and the current page is a registration page
             */
            if ($this->getIsRegistration() == 1 && $this->getHelper()->getIdinRegistrationActive() == false) {
                return;
            }

            return parent::_toHtml();
        }
    }
}

