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

class CMGroep_Idin_Helper_Customer extends Mage_Core_Helper_Abstract
{

    /**
     * Creates an user and address based off an iDIN status response
     * Used for registration through iDIN
     *
     * Returns false if creation failed
     *
     * @param string                              $emailAddress
     * @param string                              $phoneNumber
     * @param \CMGroep\Idin\Models\StatusResponse $statusResponse
     *
     * @return false|Mage_Customer_Model_Customer
     */
    public function createCustomer($emailAddress, $phoneNumber, $password = '', \CMGroep\Idin\Models\StatusResponse $statusResponse)
    {
        try {
            $websiteId = Mage::app()->getWebsite()->getId();
            $store = Mage::app()->getStore();
            $groupId = Mage::helper('customer')->getDefaultCustomerGroupId();

            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setGroupId($groupId)
                ->setFirstname($statusResponse->getName()->getInitials())
                ->setMiddlename($statusResponse->getName()->getLastNamePrefix())
                ->setLastname($statusResponse->getName()->getLastName())
                ->setEmail($emailAddress)
                ->setPassword(strlen($password) > 0 ? $password : $statusResponse->getBin())
                ->setIdinBin($statusResponse->getBin());

            $customer->save();

            $address = Mage::getModel('customer/address');
            $address->setCustomerId($customer->getId())
                ->setFirstname($customer->getFirstname())
                ->setMiddlename($customer->getMiddlename())
                ->setLastname($customer->getLastname())
                ->setCountryId($statusResponse->getAddress()->getCountry())
                ->setPostcode($statusResponse->getAddress()->getPostalCode())
                ->setCity($statusResponse->getAddress()->getCity())
                ->setStreet($statusResponse->getAddress()->getStreet() . ' ' . $statusResponse->getAddress()->getHouseNumber() . ' ' . $statusResponse->getAddress()->getHouseNumberSuffix())
                ->setTelephone(empty($phoneNumber) ? '-' : $phoneNumber)
                ->setIsDefaultBilling(1)
                ->setIsDefaultShipping(1)
                ->setSaveInAddressBook(1);

            $address->save();

            /**
             * Send iDIN registration confirmation email
             */
            $this->sendConfirmationEmail($customer);

            return $customer;
        } catch (Exception $ex) {
            Mage::logException($ex);
        }

        return false;
    }

    /**
     * Send customer registration email
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return $this
     */
    public function sendConfirmationEmail($customer)
    {
        $templateId = 'cmgroep_idin_email_confirmation';

        if (Mage::getStoreConfig('cmgroep_idin/registration/email_confirmation_template', $customer->getStore()->getId()) !=
            'cmgroep_idin_registration_email_confirmation_template') {
            $templateId = Mage::getStoreConfig('cmgroep_idin/registration/email_confirmation_template', $customer->getStore()->getId());
        }

        /** @var $mailer Mage_Core_Model_Email_Template_Mailer */
        $mailer = Mage::getModel('core/email_template_mailer');
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($customer->getEmail(), $customer->getName());
        $mailer->addEmailInfo($emailInfo);

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_REGISTER_EMAIL_IDENTITY, $customer->getStore()->getId()));
        $mailer->setStoreId($customer->getStore()->getId());
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array('customer' => $customer, 'back_url' => ''));
        $mailer->send();
    }

    /**
     * Clears current sessions and starts an authenticated
     * session for given customer
     *
     * @param Mage_Customer_model_Customer $customer
     *
     * @return bool
     */
    public function startSessionForCustomer($customer)
    {
        $session = Mage::getSingleton('customer/session');
        $session->clear();
        $session->setCustomerAsLoggedIn($customer);

        return true;
    }
}