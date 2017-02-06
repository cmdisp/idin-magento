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
    /**
     * Retrieves the session
     *
     * @return Mage_Core_Model_Session
     */
    protected function _getSession()
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

        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Invalid action'));
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
                                ->start(
                                    $this->getRequest()->getParam('idin_issuer'),
                                    $entranceCode,
                                    $dataHelper->getFinishRegistrationUrl()
                                )
                                ->withIdentity()
                                ->withName()
                                ->withAddress();

        $transactionResponse = $transaction->execute();

        /**
         * Check if transaction was successful
         */
        if ($transactionResponse instanceof \CMGroep\Idin\Models\Error) {
            $this->_getSession()->addError($transactionResponse->getMessage());
            $this->_redirectReferer();
            return;
        }

        /**
         * Save transaction for reference
         */
        $transactionLog = Mage::getModel('cmgroep_idin/transaction')
                ->setEntranceCode($entranceCode)
                ->setTransactionId($transactionResponse->getTransactionId());

        if ($this->getRequest()->has('checkout') && $this->getRequest()->getParam('checkout') == 1) {
            $transactionLog->setQuoteId(Mage::helper('checkout/cart')->getQuote()->getId());
        }

        $transactionLog->save();

        $this->_redirectUrl($transactionResponse->getIssuerAuthenticationUrl());
    }

    /**
     * Registration return URL for issuers. Shows user form to finish registration.
     */
    public function finishAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $matchingTransactionLogCollection = Mage::getResourceModel('cmgroep_idin/transaction_collection')
                                            ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                                            ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'))
                                            ->setPageSize(1);

            if ($matchingTransactionLogCollection->getSize() == 1 && $transactionLog = $matchingTransactionLogCollection->getFirstItem()) {

                /**
                 * Only retrieve response the first time, consecutive requests will fetch it from the DB.
                 */
                if ($transactionLog->getTransactionResponse()) {
                    $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($transactionLog->getTransactionId());

                    /**
                     * Check if transaction was successful
                     */
                    if ($transactionStatus instanceof \CMGroep\Idin\Models\Error) {
                        $this->_getSession()->addError($transactionStatus->getMessage());
                        $this->_redirect('customer/account/login');
                        return;
                    }

                    $transactionLog->setTransactionResponse(Mage::helper('cmgroep_idin/api')->serializeStatusResponse($transactionStatus));
                    $transactionLog->save();
                } else {
                    $transactionStatus = Mage::helper('cmgroep_idin/api')->deserializeStatusResponse($transactionLog->getTransactionResponse());
                }

                /**
                 * Check if transaction has not been cancelled
                 */
                if ($transactionStatus->getStatus() == 'cancelled') {
                    $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('The iDIN transaction has been cancelled.'));
                    $this->_redirect('customer/account/login');
                    return;
                }

                /**
                 * Check if transaction was successful
                 */
                if ($transactionStatus->getStatus() != 'success') {
                    $this->_getSession()->addNotice(Mage::helper('cmgroep_idin')->__('iDIN verification failed. Please try again later.'));
                    $this->_redirect('customer/account/login');
                    return;
                }

                /**
                 * Check if iDIN bin is already registered, if so
                 * redirect customer to login page
                 */
                if (Mage::getResourceModel('customer/customer_collection')
                        ->addFieldToFilter('idin_bin', $transactionStatus->getBin())
                        ->getSize() > 0) {
                    $this->_getSession()->addNotice(Mage::helper('cmgroep_idin')->__('An account with your identity already exists, please login in order to continue.'));
                    $this->_redirect('customer/account/login');
                    return;
                }

                $this->_getSession()->addSuccess(
                    Mage::helper('cmgroep_idin')->__('iDIN verification succeeded, please finish your registration.')
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
        if ($this->getRequest()->isPost() &&
            $this->getRequest()->has('trxid') &&
            $this->getRequest()->has('ec') && $this->getRequest()->has('email')) {
            $transactionLogCollection = Mage::getResourceModel('cmgroep_idin/transaction_collection')
                ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'))
                ->setPageSize(1);

            /** @var CMGroep_Idin_Model_Transaction $transactionLog */
            if ($transactionLogCollection->getSize() == 1 && $transactionLog = $transactionLogCollection->getFirstItem()) {
                /**
                 * Check if email address is available, otherwise
                 * return user to form
                 */
                if (Mage::getResourceModel('customer/customer_collection')
                    ->addFieldToFilter('email', $this->getRequest()->getParam('email'))
                    ->getSize() > 0) {
                    $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Emailaddress is already in use'));
                    $this->_redirect('*/*/finish', array('trxid' => $this->getRequest()->getParam('trxid'), 'ec' => $this->getRequest()->getParam('ec')));
                    return;
                }

                /**
                 * Get status of transaction
                 */
                $transactionStatus = Mage::helper('cmgroep_idin/api')->deserializeStatusResponse($transactionLog->getTransactionResponse());

                /**
                 * Verify optionally specified password
                 */
                if ($this->getRequest()->has('password') && $this->getRequest()->get('password') !== '') {
                    if ($this->getRequest()->get('password') != $this->getRequest()->get('password_confirm')) {
                        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Please make sure your passwords match'));
                        $this->_redirectReferer();
                        return;
                    }
                }

                /**
                 * Create user account and login
                 */
                if ($customer = Mage::helper('cmgroep_idin/customer')->createCustomer($this->getRequest()->getParam('email'), $this->getRequest()->getParam('phone_number'), $this->getRequest()->getParam('password'), $transactionStatus)) {
                    $transactionLog->setCustomerId($customer->getId());
                    $transactionLog->save();

                    if (Mage::helper('cmgroep_idin/customer')->startSessionForCustomer($customer)) {
                        $this->_getSession()->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully registered with iDIN!'));

                        if ($transactionLog->getQuoteId()) {
                            /** @var Mage_Sales_Model_Quote $quoteModel */
                            $quoteModel = Mage::getModel('sales/quote')->load($transactionLog->getQuoteId());
                            $quoteModel->assignCustomer($customer);
                            $quoteModel->setIsActive(true);
                            $quoteModel->save();

                            $this->_getSession()->setData('idin_checkout_method', 'customer');
                            Mage::getSingleton('checkout/session')->loadCustomerQuote();
                            $this->_redirectUrl(Mage::helper('checkout/url')->getCheckoutUrl());
                        } else {
                            $this->_redirect('customer/account');
                        }
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

        /**
         * Check if transaction was successful
         */
        if ($transactionResponse instanceof \CMGroep\Idin\Models\Error) {
            $this->_getSession()->addError($transactionResponse->getMessage());
            $this->_redirectReferer();
            return;
        }

        $dataHelper->registerTransaction($transactionResponse->getTransactionId(), $entranceCode);

        $this->_redirectUrl($transactionResponse->getIssuerAuthenticationUrl());
    }

    /**
     * Starts a new session based on the identified customer's bin token
     */
    public function authAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($this->getRequest()->getParam('trxid'));

            /**
             * Check if transaction was successful
             */
            if ($transactionStatus instanceof \CMGroep\Idin\Models\Error) {
                $this->_getSession()->addError($transactionStatus->getMessage());
                $this->_redirect('customer/account/login');
                return;
            }

            Mage::helper('cmgroep_idin')->registerTransaction($this->getRequest()->getParam('trxid'), $this->getRequest()->getParam('ec'), $transactionStatus);

            if ($transactionStatus->getStatus() == 'success') {
                $idinBin = $transactionStatus->getBin();
                $customerCollection = Mage::getResourceModel('customer/customer_collection')
                                    ->addFieldToFilter('idin_bin', $idinBin)
                                    ->setPageSize(1);

                if ($customerCollection->getSize() == 1) {
                    $customer = $customerCollection->getFirstItem();

                    if (Mage::helper('cmgroep_idin/customer')->startSessionForCustomer($customer)) {
                        $this->_getSession()->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully logged in with iDIN!'));
                        $this->_redirect('customer/account');
                    } else {
                        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Could not create a new customer session!'));
                        $this->_redirect('/');
                    }
                } else {
                    $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Could not find a matching account, please make sure your account is linked with iDIN or create an account by registering with iDIN below.'));
                    $this->_redirect('customer/account/login');
                    return;
                }
            } elseif ($transactionStatus->getStatus() == 'cancelled') {
                $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('The iDIN transaction has been cancelled.'));
                $this->_redirect('customer/account/login');
                return;
            } else {
                $this->_getSession()->addNotice(Mage::helper('cmgroep_idin')->__('iDIN transaction failed. Please try again later.'));
                $this->_redirect('customer/account/login');
                return;
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

                    $transactionResponse = $transaction->execute();

                    /**
                     * Check if transaction was successful
                     */
                    if ($transactionResponse instanceof \CMGroep\Idin\Models\Error) {
                        $this->_getSession()->addError($transactionResponse->getMessage());
                        $this->_redirectReferer();
                        return;
                    }

                    /**
                     * Log transaction referencing existing customer
                     */
                    Mage::getModel('cmgroep_idin/transaction')
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
            $matchingTransactions = Mage::getResourceModel('cmgroep_idin/transaction_collection')
                ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'))
                ->setPageSize(1);

            if ($matchingTransactions->getSize() == 1) {
                /** @var CMGroep_Idin_Model_Transaction $transaction */
                $transaction = $matchingTransactions->getFirstItem();
                $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($transaction->getTransactionId());

                /**
                 * Check if transaction was successful
                 */
                if ($transactionStatus instanceof \CMGroep\Idin\Models\Error) {
                    $this->_getSession()->addError($transactionStatus->getMessage());
                    $this->_redirect('customer/account/index');
                    return;
                }

                Mage::helper('cmgroep_idin')->registerTransaction($transaction->getTransactionId(), $transaction->getEntranceCode(), $transactionStatus);

                /**
                 * Check if transaction has not been cancelled
                 */
                if ($transactionStatus->getStatus() == 'cancelled') {
                    $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('The iDIN transaction has been cancelled.'));
                    $this->_redirect('customer/account/index');
                    return;
                }

                /**
                 * Check if transaction was successful
                 */
                if ($transactionStatus->getStatus() != 'success') {
                    $this->_getSession()->addNotice(Mage::helper('cmgroep_idin')->__('iDIN transaction failed. Please try again later.'));
                    $this->_redirect('customer/account/login');
                    return;
                }

                /**
                 * Cache Customer ID and remove transaction record
                 */
                $registrationCustomerId = $transaction->getCustomerId();

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
    }

    /**
     * Starts a new age verification transaction
     * Supported from both the checkout as the customer's my account page
     */
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
            $transactionLog = Mage::getModel('cmgroep_idin/transaction')
                ->setEntranceCode($entranceCode);

            if (Mage::helper('customer')->isLoggedIn()) {
                $customer = Mage::helper('customer')->getCurrentCustomer();
                $transactionLog->setCustomerId($customer->getId());
            }

            if ($this->getRequest()->get('mode') == 'checkout') {
                $transactionLog->setQuoteId(Mage::helper('checkout')->getQuote()->getId());
            }

            if (($this->getRequest()->has('mode') && $this->getRequest()->getParam('mode') == 'checkout') ||
                ($this->getRequest()->has('mode') == false && Mage::helper('customer')->isLoggedIn())) {
                $transaction = Mage::helper('cmgroep_idin/api_transaction')
                    ->start($this->getRequest()->getParam('idin_issuer'), $entranceCode, $verifyAgeUrl)
                    ->withIdentity()
                    ->with18yOrOlder();

                $transactionResponse = $transaction->execute();

                /**
                 * Check if transaction was successful
                 */
                if ($transactionResponse instanceof \CMGroep\Idin\Models\Error) {
                    $this->_getSession()->addError($transactionResponse->getMessage());
                    $this->_redirectReferer();
                    return;
                }

                $transactionLog->setTransactionId($transactionResponse->getTransactionId())
                    ->save();

                $this->_redirectUrl($transactionResponse->getIssuerAuthenticationUrl());
                return;
            }
        }

        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Invalid request data'));
        $this->_redirectReferer();
    }

    /**
     * Callback function for iDIN age verification transaction
     * Stores the result of the transaction on the customer
     */
    public function verifyAgeFinishAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $matchingTransactions = Mage::getResourceModel('cmgroep_idin/transaction_collection')
                ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'))
                ->setPageSize(1);

            if ($matchingTransactions->getSize() == 1) {
                /** @var CMGroep_Idin_Model_Transaction $transaction */
                $transaction = $matchingTransactions->getFirstItem();
                $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($transaction->getTransactionId());

                /**
                 * Check if transaction was successful
                 */
                if ($transactionStatus instanceof \CMGroep\Idin\Models\Error) {
                    $this->_getSession()->addError($transactionStatus->getMessage());
                    $this->_redirect('customer/account/index');
                    return;
                }

                Mage::helper('cmgroep_idin')->registerTransaction($transaction->getTransactionId(), $transaction->getEntranceCode(), $transactionStatus);

                /**
                 * Check if transaction has not been cancelled
                 */
                if ($transactionStatus->getStatus() == 'cancelled') {
                    $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('The iDIN transaction has been cancelled.'));
                    $this->_redirect('customer/account/index');
                    return;
                }

                /**
                 * Check if transaction was successful
                 */
                if ($transactionStatus->getStatus() != 'success') {
                    $this->_getSession()->addNotice(Mage::helper('cmgroep_idin')->__('iDIN verification failed. Please try again later.'));
                    $this->_redirect('customer/account/index');
                    return;
                }

                /*
                 * Store result of age verification
                 */
                $customer = Mage::getModel('customer/customer')->load($transaction->getCustomerId());
                $customer->setIdinAgeVerified($transactionStatus->getAge()->get18yOrOlder() ? 1 : 0);
                $customer->save();

                $this->_getSession()->addSuccess(Mage::helper('cmgroep_idin')->__('Succesfully verified your age with iDIN!'));
                $this->_redirect('customer/account/index');
                return;
            }
        }

        $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('Invalid request data'));
        $this->_redirect('/');
    }

    /**
     * Callback function for iDIN age verification transaction
     * Stores the result of the transaction on the current quote
     * and if a customer is logged in, it's also saved for recurring visits
     */
    public function verifyAgeCheckoutFinishAction()
    {
        if ($this->getRequest()->has('trxid') && $this->getRequest()->has('ec')) {
            $matchingTransactions = Mage::getResourceModel('cmgroep_idin/transaction_collection')
                ->addFieldToFilter('transaction_id', $this->getRequest()->getParam('trxid'))
                ->addFieldToFilter('entrance_code', $this->getRequest()->getParam('ec'))
                ->setPageSize(1);

            if ($matchingTransactions->getSize() == 1) {
                /** @var CMGroep_Idin_Model_Transaction $transaction */
                $transaction = $matchingTransactions->getFirstItem();
                $transactionStatus = Mage::helper('cmgroep_idin/api')->getTransactionStatus($this->getRequest()->getParam('trxid'));

                /**
                 * Check if transaction was successful
                 */
                if ($transactionStatus instanceof \CMGroep\Idin\Models\Error) {
                    $this->_getSession()->addError($transactionStatus->getMessage());
                    $this->_redirectUrl(Mage::helper('checkout/url')->getCheckoutUrl());
                    return;
                }

                Mage::helper('cmgroep_idin')->registerTransaction($transaction->getTransactionId(), $transaction->getEntranceCode(), $transactionStatus);

                /**
                 * Check if transaction has not been cancelled
                 */
                if ($transactionStatus->getStatus() == 'cancelled') {
                    $this->_getSession()->addError(Mage::helper('cmgroep_idin')->__('The iDIN transaction has been cancelled.'));
                    $this->_redirectUrl(Mage::helper('checkout/url')->getCheckoutUrl());
                    return;
                }

                /**
                 * Check if transaction was not successful
                 */
                if ($transactionStatus->getStatus() != 'success') {
                    $this->_getSession()->addNotice(Mage::helper('cmgroep_idin')->__('iDIN verification failed. Please try again later.'));
                    $this->_redirectUrl(Mage::helper('checkout/url')->getCheckoutUrl());
                    return;
                }

                /**
                 * If customer was logged in during checkout, save verification for recurring visits
                 */
                if ($transaction->getCustomerId() !== null) {
                    $customer = Mage::getModel('customer/customer')->load($transaction->getCustomerId());
                    $customer->setIdinAgeVerified($transactionStatus->getAge()->get18yOrOlder() ? 1 : 0);
                    $customer->save();
                }

                /**
                 * Save verification result on quote
                 */
                if ($transaction->getQuoteId() !== null) {
                    /** @var Mage_Sales_Model_Quote $quote */
                    $quote = Mage::getModel('sales/quote')->load($transaction->getQuoteId());
                    $quote->setIdinAgeVerified($transactionStatus->getAge()->get18yOrOlder() ? 1 : 0);
                    $quote->save();
                }

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