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

class CMGroep_Idin_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CACHE_TAG_IDIN = 'cmgroep_idin';
    const CACHE_KEY_IDIN_ISSUERS = 'cmgroup_idin_issuers';

    /**
     * Returns whether login is enabled or not
     *
     * @return bool
     */
    public function getExtensionActive()
    {
        return Mage::getStoreConfig('cmgroep_idin/common/active') == 1;
    }

    /**
     * Retrieves the current mode
     *
     * @return integer
     */
    public function getExtensionMode()
    {
        return Mage::getStoreConfig('cmgroep_idin/common/mode');
    }

    /**
     * Retrieves the merchant token
     *
     * @return string
     */
    public function getMerchantToken()
    {
        return Mage::getStoreConfig('cmgroep_idin/common/merchant_token');
    }

    /**
     * Returns whether registration is enabled or not
     *
     * @return bool
     */
    public function getIdinRegistrationActive()
    {
        return Mage::getStoreConfig('cmgroep_idin/registration/active') == 1;
    }

    /**
     * Returns whether login is enabled or not
     *
     * @return bool
     */
    public function getIdinLoginActive()
    {
        return Mage::getStoreConfig('cmgroep_idin/login/active') == 1;
    }

    /**
     * Returns whether age verification is enabled or not
     *
     * @return bool
     */
    public function getIdinAgeVerificationActive()
    {
        return Mage::getStoreConfig('cmgroep_idin/age_verification/active') == 1;
    }

    /**
     * Retrieves the age requirement setting
     *
     * @return int
     */
    public function getIdinAgeVerificationRequired()
    {
        return Mage::getStoreConfig('cmgroep_idin/age_verification/required');
    }

    /**
     * Determines if the age verification result should be saved
     *
     * @return bool
     */
    public function getIdinSaveAgeVerificationResult()
    {
        return Mage::getStoreConfig('cmgroep_idin/age_verification/save_verification_result') == 1;
    }

    /**
     * Determines is product notice is enabled
     *
     * @return bool
     */
    public function getIdinAgeVerificationProductNoticeSetting()
    {
        return Mage::getStoreConfig('cmgroep_idin/age_verification/show_product_notice');
    }

    /**
     * Determines is cart notice is enabled
     *
     * @return bool
     */
    public function getIdinAgeVerificationCartNoticeEnabled()
    {
        return Mage::getStoreConfig('cmgroep_idin/age_verification/show_cart_notice') == 1;
    }

    public function registerTransaction($transactionId, $entranceCode, \CMGroep\Idin\Models\StatusResponse $statusResponse = null)
    {
        if (is_null($statusResponse)) {
            Mage::getModel('cmgroep_idin/transaction')
                ->setTransactionId($transactionId)
                ->setEntranceCode($entranceCode)
                ->save();
        } else {
            $matchingTransactionLogCollection = Mage::getResourceModel('cmgroep_idin/transaction_collection')
                ->addFieldToFilter('transaction_id', $transactionId)
                ->addFieldToFilter('entrance_code', $entranceCode);

            if ($matchingTransactionLogCollection->count() == 1 && $transactionLog = $matchingTransactionLogCollection->getFirstItem()) {
                $serializedResponse = Mage::helper('cmgroep_idin/api')->serializeStatusResponse($statusResponse);
                $transactionLog->setTransactionResponse($serializedResponse);
                $transactionLog->save();
            }
        }
    }

    /**
     * Retrieves the cart notice to be shown
     *
     * @return string
     */
    public function getIdinAgeCartVerificationNotice()
    {
        return Mage::getStoreConfig('cmgroep_idin/age_verification/cart_notice');
    }

    /**
     * Retrieves the product notice to be shown
     *
     * @return string
     */
    public function getIdinAgeProductVerificationNotice()
    {
        return Mage::getStoreConfig('cmgroep_idin/age_verification/product_notice');
    }

    /**
     * Retrieves return url for registration actions
     *
     * @return string
     */
    public function getFinishRegistrationUrl()
    {
        return Mage::getUrl('idin/auth/finish');
    }

    /**
     * Retrieves return url for login actions
     *
     * @return string
     */
    public function getAuthReturnUrl()
    {
        return Mage::getUrl('idin/auth/auth');
    }

    /**
     * Retrieves return url for connecting existing accounts
     *
     * @return string
     */
    public function getConnectReturnUrl()
    {
        return Mage::getUrl('idin/auth/connectFinish');
    }

    /**
     * Retrieves the return url for age verification
     *
     * @return string
     */
    public function getVerifyAgeReturnUrl()
    {
        return Mage::getUrl('idin/auth/verifyAgeFinish');
    }

    /**
     * Retrieves the return url for age verification initiated from
     * the checkout
     *
     * @param $checkoutMethod
     *
     * @return string
     */
    public function getVerifyAgeCheckoutReturnUrl($checkoutMethod)
    {
        return Mage::getUrl('idin/auth/verifyAgeCheckoutFinish', ['checkout_method' => $checkoutMethod]);
    }

    /**
     * Returns list of issuers grouped by country
     *
     * @return array
     */
    public function getIssuerList()
    {
        /**
         * Logic to retrieve issuers if not cached or expired (24h)
         */
        $cache = Mage::app()->getCache();

        if ($issuers = $cache->load(self::CACHE_KEY_IDIN_ISSUERS)) {
            return json_decode($issuers, true);
        } else {
            $issuersPerCountry = array();

            $idinDirectories = Mage::helper('cmgroep_idin/api')->getDirectory();

            /** @var \CMGroep\Idin\Models\DirectoryResponse $idinDirectory */
            foreach ($idinDirectories as $idinDirectory) {
                foreach ($idinDirectory->getIssuers() as $issuer) {
                    $issuersPerCountry[$idinDirectory->getCountry()][] = [
                        'issuer_id' => $issuer->getIssuerId(),
                        'issuer_name' => $issuer->getIssuerName()
                    ];
                }
            }

            /**
             * Save issuers for 7d in cache
             */
            $cache->save(json_encode($issuersPerCountry), self::CACHE_KEY_IDIN_ISSUERS, array(self::CACHE_TAG_IDIN), 60*60*24*7);

            return $issuersPerCountry;
        }
    }

    /**
     * Determines if verification is required
     * Checks:
     *  - Age Verification required or 18+ products
     *  - 18+ products in cart
     *  - Customers age verification status
     *
     * @param bool $skipCustomerAndQuoteCheck Skip the check if the session has been verified, just check if it should have been done
     *
     * @return bool
     */
    public function ageVerificationRequired($skipCustomerAndQuoteCheck = false)
    {
        $customerHelper = Mage::helper('customer');
        $ageVerified = false;

        if ($skipCustomerAndQuoteCheck == false) {
            $quote = Mage::helper('checkout/cart')->getQuote();

            if ($customerHelper->isLoggedIn()) {
                /**
                 * Check customers verification status
                 */
                if ($customerHelper->getCurrentCustomer()->getIdinAgeVerified()) {
                    $ageVerified = true;
                }
            } else {
                /**
                 * Check current quote session verification status
                 */
                if ($quote->getIdinAgeVerified()) {
                    $ageVerified = true;
                }
            }

            /**
             * Always verify the age when not saved and quote has not yet been verified
             */
            if ($this->getIdinSaveAgeVerificationResult() == false && $quote->getIdinAgeVerified() == false) {
                $ageVerified = false;
            }

            /**
             * Skip extra processing if age is verified
             */
            if ($ageVerified) {
                return false;
            }
        }

        if ($this->getIdinAgeVerificationRequired() == CMGroep_Idin_Model_System_Config_Source_Verificationrequired::MODE_ALWAYS) {
            /**
             * Verification is always required
             */
            return $ageVerified == false;
        }
        elseif ($this->getIdinAgeVerificationRequired() == CMGroep_Idin_Model_System_Config_Source_Verificationrequired::MODE_PRODUCTS) {
            /**
             * Check cart for 18+ products
             */

            /** @var Mage_Sales_Model_Quote_Item[] $cartItems */
            $cartItems = Mage::helper('checkout/cart')->getQuote()->getAllItems();

            /** @var Mage_Sales_Model_Quote_Item $productIds */
            $cartProductIds = array_map(function($cartItem) {
                return $cartItem->getProductId();
            }, $cartItems);

            $productsWithRequiredAgeVerification = Mage::getResourceModel('catalog/product_collection')
                ->addAttributeToSelect('idin_require_age_verification')
                ->addFieldToFilter('entity_id', array('in' => $cartProductIds))
                ->addAttributeToFilter('idin_require_age_verification', 1);

            if ($productsWithRequiredAgeVerification->count() > 0) {
                return $ageVerified == false;
            }
        }

        return false;
    }
}