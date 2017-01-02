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
    private function _getSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * Dispatches the form request to the correct action based on the action
     * picked by the user
     */
    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {

            if ($this->getRequest()->has('form_action') && $this->getRequest()->has('idin_issuer')) {

                if ($this->getRequest()->get('idin_issuer') == '') {
                    $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Please select your bank'));
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

        $this->_getSession()->addSuccess(Mage::helper('cmgroep_idin')->__('Invalid action'));
        $this->_redirectReferer();
    }

    /**
     * Starts new iDIN transaction for registration and redirects the
     * user to the Authentication URL of issuer
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
         * Save transaction for reference
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

            if ($matchingRegistrationCollection->count() == 1 && $registration = $matchingRegistrationCollection->getFirstItem()) {
                $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($registration->getTransactionId());
                $registration->setTransactionResponse(Mage::helper('cmgroep_idin/api')->serializeStatusResponse($transactionStatus));
                $registration->save();

                /**
                 * Check if iDIN bin is already registered, if so
                 * redirect customer to login page
                 */
                if (Mage::getResourceModel('customer/customer_collection')
                        ->addFieldToFilter('idin_bin', $transactionStatus->getBin())
                        ->count() > 0) {

                    /**
                     * Delete registration record since bin already exists
                     */
                    $registration->delete();

                    $this->_getSession()->addNotice(Mage::helper('cmgroep_idin')->__('An account with your identity already exists, please login in order to continue.'));
                    $this->_redirect('customer/account/login');
                    return;
                }

                $this->_getSession()->addSuccess(
                    Mage::helper('cmgroep_idin')->__('iDIN verification succeeded, please finish your registration. Your iDIN session will expire after 5 minutes.')
                );

                $this->loadLayout();
                $this->renderLayout();
                return;
            }
        }

        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Invalid data returned from iDIN issuer'));
        $this->_redirect('customer/account/login');
    }

    /**
     * Finishes registration after entering an email address
     * and starts a session for the newly created user
     */
    public function registerAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec') && $this->getRequest()->has('email')) {

                $registrationCollection = Mage::getResourceModel('cmgroep_idin/registration_collection')
                    ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                    ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'));

                if ($registrationCollection->count() == 1 && $registration = $registrationCollection->getFirstItem()) {
                    /**
                     * Check if email address is available, otherwise
                     * return user to form
                     */
                    if (Mage::getResourceModel('customer/customer_collection')
                        ->addFieldToFilter('email', $this->getRequest()->getParam('email'))
                        ->count() > 0) {
                        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Emailaddress is already in use'));
                        $this->_redirect('*/*/finish', ['trxid' => $this->getRequest()->getParam('trxid'), 'ec' => $this->getRequest()->getParam('ec')]);
                        return;
                    }

                    /**
                     * Get status of transaction and delete transaction record
                     */
                    $transactionStatus = Mage::helper('cmgroep_idin/api')->deserializeStatusResponse($registration->getTransactionResponse());
                    $registration->delete();

                    /**
                     * Create user account and login
                     */
                    if ($customer = Mage::helper('cmgroep_idin/customer')->createCustomer($this->getRequest()->getParam('email'), $transactionStatus)) {
                        if (Mage::helper('cmgroep_idin/customer')->startSessionForCustomer($customer)) {
                            $this->_getSession()->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully registered with iDIN!'));
                            $this->_redirect('customer/account');
                        } else {
                            $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Could not create a new customer session!'));
                            $this->_redirect('/');
                        }
                    } else {
                        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Failed to create a new account!'));
                        $this->_redirect('/');
                    }
                }
            }
        }
    }

    /**
     * Starts a new iDIN identity transaction and redirects user to the
     * Authentication URL of issuer
     */
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

    /**
     * Starts a new session based on the identified customer's bin token
     */
    public function authAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($this->getRequest()->get('trxid'));

            if ($transactionStatus->getStatus() == 'success') {
                $idinBin = $transactionStatus->getBin();
                $customerCollection = Mage::getResourceModel('customer/customer_collection')
                                    ->addFieldToFilter('idin_bin', $idinBin);

                if ($customerCollection->count() == 1) {
                    $customer = $customerCollection->getFirstItem();

                    if (Mage::helper('cmgroep_idin/customer')->startSessionForCustomer($customer)) {
                        $this->_getSession()->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully logged in with iDIN!'));
                        $this->_redirect('customer/account');
                    } else {
                        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Could not create a new customer session!'));
                        $this->_redirect('/');
                    }
                } else {
                    $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Could not find a matching account, please make sure your account is linked with iDIN.'));
                    $this->_redirect('customer/account/login');
                }
            }
        }
    }
}