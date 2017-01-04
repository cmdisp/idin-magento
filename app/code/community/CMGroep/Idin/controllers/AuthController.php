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

        $transaction = Mage::helper('cmgroep_idin/api_transaction')
                                ->start($this->getRequest()->getParam('idin_issuer'), $entranceCode, $dataHelper->getFinishRegistrationUrl())
                                ->withIdentity()
                                ->withName()
                                ->withAddress();

        /**
         * If age verification is enabled, add it to the request
         */
        if ($dataHelper->getIdinAgeVerificationActive()) {
            $transaction->with18yOrOlder();
        }

        $transactionResponse = $transaction->execute();

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

    /**
     * Starts a new iDIN transaction for connecting an existing account
     * with iDIN
     */
    public function connectAction()
    {
        if ($this->getRequest()->isPost() && $this->getRequest()->has('idin_issuer')) {
            if (Mage::helper('customer')->isLoggedIn()) {
                $customer = Mage::helper('customer')->getCurrentCustomer();

                /**
                 * Check if customer is not connected to iDIN already
                 */
                if (empty($customer->getIdinBin())) {
                    $dataHelper = Mage::helper('cmgroep_idin');
                    $apiHelper = Mage::helper('cmgroep_idin/api');
                    $entranceCode = $apiHelper->generateEntranceCode();

                    $transaction = Mage::helper('cmgroep_idin/api_transaction')
                        ->start($this->getRequest()->getParam('idin_issuer'), $entranceCode, $dataHelper->getConnectReturnUrl())
                        ->withIdentity();

                    /**
                     * If age verification is enabled, add it to the request
                     */
                    if ($dataHelper->getIdinAgeVerificationActive()) {
                        $transaction->with18yOrOlder();
                    }

                    $transactionResponse = $transaction->execute();

                    /**
                     * Log transaction referencing existing customer
                     */
                    Mage::getModel('cmgroep_idin/registration')
                        ->setTransactionId($transactionResponse->getTransactionId())
                        ->setEntranceCode($entranceCode)
                        ->setCustomerId($customer->getId())
                        ->save();

                    $this->_redirectUrl($transactionResponse->getIssuerAuthenticationUrl());
                    return;
                }
            }
        }

        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Invalid request data'));
        $this->_redirectReferer();
    }

    /**
     * Callback function for iDIN transaction
     * Saves iDIN details to connect an existing account
     */
    public function connectFinishAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $matchingRegistrations = Mage::getResourceModel('cmgroep_idin/registration_collection')
                ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'));

            if ($matchingRegistrations->count() == 1) {
                /** @var CMGroep_Idin_Model_Registration $registration */
                $registration = $matchingRegistrations->getFirstItem();
                $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($registration->getTransactionId());

                /**
                 * Cache Customer ID and remove registration record
                 */
                $registrationCustomerId = $registration->getCustomerId();
                $registration->delete();

                /**
                 * Check if bin already exists
                 */
                $customersWithSameBin = Mage::getResourceModel('customer/customer_collection')
                    ->addFieldToFilter('idin_bin', $transactionStatus->getBin())
                    ->count();

                if ($customersWithSameBin == 0) {
                    $customer = Mage::getModel('customer/customer')->load($registrationCustomerId);
                    $customer->setIdinBin($transactionStatus->getBin());

                    if (Mage::helper('cmgroep_idin')->getIdinAgeVerificationActive()) {
                        $customer->setIdinAgeVerified($transactionStatus->getAge()->get18yOrOlder() ? 1 : 0);
                    }

                    $customer->save();

                    $this->_getSession()->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully connected your account with iDIN!'));
                    $this->_redirect('customer/account/index');
                    return;
                } else {
                    $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('A customer with the same iDIN identity already exists!'));
                    $this->_redirect('customer/account/index');
                    return;
                }
            }
        }

        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Invalid request data'));
        $this->_redirect('/');
        return;
    }

    public function verifyAgeAction()
    {
        if ($this->getRequest()->isPost() && $this->getRequest()->has('idin_issuer')) {
            $dataHelper = Mage::helper('cmgroep_idin');
            $apiHelper = Mage::helper('cmgroep_idin/api');
            $entranceCode = $apiHelper->generateEntranceCode();

            $verifyAgeUrl = $dataHelper->getVerifyAgeReturnUrl();
            if ($this->getRequest()->has('mode') && $this->getRequest()->getParam('mode') == 'checkout') {
                $verifyAgeUrl = $dataHelper->getVerifyAgeCheckoutReturnUrl($this->getRequest()->getParam('checkout_method'));
            }

            /**
             * Log transaction referencing existing customer or quote
             */
            $registration = Mage::getModel('cmgroep_idin/registration')
                ->setEntranceCode($entranceCode);

            if (Mage::helper('customer')->isLoggedIn()) {
                $customer = Mage::helper('customer')->getCurrentCustomer();
                $registration->setCustomerId($customer->getId());
            }

            if ($this->getRequest()->get('mode') == 'checkout') {
                $registration->setQuoteId(Mage::helper('checkout')->getQuote()->getId());
            }

            if (($this->getRequest()->has('mode') && $this->getRequest()->getParam('mode') == 'checkout') ||
                ($this->getRequest()->has('mode') == false && Mage::helper('customer')->isLoggedIn())) {
                $transaction = Mage::helper('cmgroep_idin/api_transaction')
                    ->start($this->getRequest()->getParam('idin_issuer'), $entranceCode, $verifyAgeUrl)
                    ->withIdentity()
                    ->with18yOrOlder();

                $transactionResponse = $transaction->execute();

                $registration->setTransactionId($transactionResponse->getTransactionId())
                    ->save();

                $this->_redirectUrl($transactionResponse->getIssuerAuthenticationUrl());
                return;
            }
        }

        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Invalid request data'));
        $this->_redirectReferer();
    }

    public function verifyAgeFinishAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $matchingRegistrations = Mage::getResourceModel('cmgroep_idin/registration_collection')
                ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'));

            if ($matchingRegistrations->count() == 1) {
                /** @var CMGroep_Idin_Model_Registration $registration */
                $registration = $matchingRegistrations->getFirstItem();
                $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($registration->getTransactionId());

                /**
                 * Cache Customer ID and remove registration record
                 */
                $registrationCustomerId = $registration->getCustomerId();
                $registration->delete();

                /**
                 * Check if bin already exists
                 */
                $customersWithBin = Mage::getResourceModel('customer/customer_collection')
                    ->addFieldToFilter('idin_bin', $transactionStatus->getBin());

                if ($customersWithBin->count() == 1) {
                    $customer = Mage::getModel('customer/customer')->load($registrationCustomerId);

                    $customer->setIdinAgeVerified($transactionStatus->getAge()->get18yOrOlder() ? 1 : 0);
                    $customer->save();

                    $this->_getSession()->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully verified your age with iDIN!'));
                    $this->_redirect('customer/account/index');
                    return;
                }
            }
        }

        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Invalid request data'));
        $this->_redirect('/');
        return;
    }

    public function verifyAgeCheckoutFinishAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $matchingRegistrations = Mage::getResourceModel('cmgroep_idin/registration_collection')
                ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'));

            if ($matchingRegistrations->count() == 1) {
                /** @var CMGroep_Idin_Model_Registration $registration */
                $registration = $matchingRegistrations->getFirstItem();
                $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($this->getRequest()->getParam('trxid'));

                /**
                 * If customer was logged in during checkout, save verification for recurring visits
                 */
                if (is_null($registration->getCustomerId()) == false) {
                    $customer = Mage::getModel('customer/customer')->load($registration->getCustomerId());
                    $customer->setIdinAgeVerified($transactionStatus->getAge()->get18yOrOlder() ? 1 : 0);
                    $customer->save();
                }

                /**
                 * Save verification result on quote
                 */
                if (is_null($registration->getQuoteId()) == false) {
                    $quote = Mage::getModel('sales/quote')->load($registration->getQuoteId());
                    $quote->setIdinAgeVerified($transactionStatus->getAge()->get18yOrOlder() ? 1 : 0);
                    $quote->save();
                }

                $registration->delete();

                $this->_getSession()->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully verified your age with iDIN!'));
                $this->_getSession()->setData('idin_checkout_method', $this->getRequest()->getParam('checkout_method'));
                $this->_redirectUrl(Mage::helper('checkout/url')->getCheckoutUrl());
                return;
            }
        }

        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Invalid request data'));
        return $this->_redirect('/');
    }
}