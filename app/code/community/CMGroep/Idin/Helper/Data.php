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
             * Save issuers for 24h in cache
             */
            $cache->save(json_encode($issuersPerCountry), self::CACHE_KEY_IDIN_ISSUERS, array(self::CACHE_TAG_IDIN), 60*60*24);

            return $issuersPerCountry;
        }
    }
}