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

class CMGroep_Idin_AuthController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {

            if ($this->getRequest()->has('form_action') && $this->getRequest()->has('idin_issuer')) {

                if ($this->getRequest()->get('idin_issuer') == '') {
                    Mage::getSingleton('core/session')->addError(Mage::helper('cmgroep_idin')->__('Please select your bank'));
                    $this->_redirectReferer();
                    return;
                }

                if ($this->getRequest()->getParam('form_action') == 'register') {
                    $this->_redirect('*/*/registration', $this->getRequest()->getParams());
                    return;
                } else if ($this->getRequest()->getParam('form_action') == 'login') {
                    $this->_redirect('*/*/login', $this->getRequest()->getParams());
                    return;
                }
            }
        }

        Mage::getSingleton('core/session')->addSuccess(Mage::helper('cmgroep_idin')->__('Invalid action'));
        $this->_redirectReferer();
    }

    /**
     * Starts new iDIN transaction for registration
     * and redirects user to Authentication URL of issuer
     *
     * @throws Exception
     */
    public function registrationAction()
    {
        $dataHelper = Mage::helper('cmgroep_idin');
        $apiHelper = Mage::helper('cmgroep_idin/api');
        $entranceCode = $apiHelper->generateEntranceCode();

        $transactionResponse = Mage::helper('cmgroep_idin/api_transaction')
                                ->start($this->getRequest()->getParam('idin_issuer'), $entranceCode, $dataHelper->getFinishRegistrationUrl())
                                ->withIdentity()
                                ->withName()
                                ->withAddress()
                                ->execute();

        /**
         * Save transaction for later reference
         */
        Mage::getModel('cmgroep_idin/registration')
                ->setEntranceCode($entranceCode)
                ->setTransactionId($transactionResponse->getTransactionId())
                ->save();

        $this->_redirectUrl($transactionResponse->getIssuerAuthenticationUrl());
    }

    /**
     * Registration return URL for issuers. Shows user form to finish registration.
     */
    public function finishAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $matchingRegistrationCollection = Mage::getResourceModel('cmgroep_idin/registration_collection')
                                            ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                                            ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'));

            if ($matchingRegistrationCollection->count()) {
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('cmgroep_idin')->__('iDIN verification succeeded, please finish your registration. Your iDIN session will expire after 5 minutes.')
                );

                $this->loadLayout();
                $this->renderLayout();
                return;
            }
        }

        Mage::getSingleton('core/session')->addError(Mage::helper('cmgroep_idin')->__('Invalid data returned from iDIN issuer'));
        $this->_redirect('customer/account/login');
    }

    public function registerAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec') && $this->getRequest()->has('email')) {

                $registrationCollection = Mage::getResourceModel('cmgroep_idin/registration_collection')
                    ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                    ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'));

                if ($registrationCollection->count() == 1 && $registration = $registrationCollection->getFirstItem()) {
                    $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($registration->getTransactionId());

                    $customer = Mage::helper('cmgroep_idin/customer')->createCustomer($this->getRequest()->getParam('email'), $transactionStatus);

                    $session = Mage::getSingleton('customer/session');
                    $session->clear();
                    $session->setCustomerAsLoggedIn($customer);

                    Mage::getSingleton('core/session')->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully registered with iDIN!'));
                    $this->_redirect('customer/account');
                }
            }
        }
    }

    public function loginAction()
    {
        $dataHelper = Mage::helper('cmgroep_idin');
        $apiHelper = Mage::helper('cmgroep_idin/api');
        $entranceCode = $apiHelper->generateEntranceCode();

        $transactionResponse = Mage::helper('cmgroep_idin/api_transaction')
            ->start($this->getRequest()->getParam('idin_issuer'), $entranceCode, $dataHelper->getAuthReturnUrl())
            ->withIdentity()
            ->execute();

        $this->_redirectUrl($transactionResponse->getIssuerAuthenticationUrl());
    }

    public function authAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($this->getRequest()->get('trxid'));

            if($transactionStatus->getStatus() == 'success') {

                $idinBin = $transactionStatus->getBin();
                $customerCollection = Mage::getResourceModel('customer/customer_collection')
                                    ->addFieldToFilter('idin_bin', $idinBin);

                if ($customerCollection->count() == 1) {
                    $customer = $customerCollection->getFirstItem();

                    $session = Mage::getSingleton('customer/session');
                    $session->clear();

                    $session->setCustomerAsLoggedIn($customer);

                    Mage::getSingleton('core/session')->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully logged in with iDIN!'));
                    $this->_redirect('customer/account');
                }
            }
        }
    }
}