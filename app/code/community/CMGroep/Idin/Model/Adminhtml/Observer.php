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
class CMGroep_Idin_Model_Adminhtml_Observer
{

    /**
     * Handles configuration changes
     *  - Retrieve available services
     *  - Enable/Disable specific product attributes
     *
     * @param $event
     */
    public function processConfigurationChanges($event)
    {
        $this->refreshAvailableServices();
        $this->updateIdinProductAttributes();
    }

    /**
     * Retrieves available services based on selected mode and merchant_token
     */
    private function refreshAvailableServices()
    {
        $merchantToken = Mage::getStoreConfig('cmgroep_idin/common/merchant_token');

        if (strlen($merchantToken) > 0) {
            $apiHelper = Mage::helper('cmgroep_idin/api');
            $merchantInformation = $apiHelper->getMerchantInformation($merchantToken);

            /**
             * Save available services into hidden config
             */
            Mage::getConfig()->saveConfig('cmgroep_idin/services/identity', $merchantInformation->getServices()->getIdentity());
            Mage::getConfig()->saveConfig('cmgroep_idin/services/name', $merchantInformation->getServices()->getName());
            Mage::getConfig()->saveConfig('cmgroep_idin/services/address', $merchantInformation->getServices()->getAddress());
            Mage::getConfig()->saveConfig('cmgroep_idin/services/date_of_birth', $merchantInformation->getServices()->getDateOfBirth());
            Mage::getConfig()->saveConfig('cmgroep_idin/services/18y_or_older', $merchantInformation->getServices()->get18yOrOlder());
        } else {
            Mage::getConfig()->saveConfig('cmgroep_idin/services/identity', 0);
            Mage::getConfig()->saveConfig('cmgroep_idin/services/name', 0);
            Mage::getConfig()->saveConfig('cmgroep_idin/services/address', 0);
            Mage::getConfig()->saveConfig('cmgroep_idin/services/date_of_birth', 0);
            Mage::getConfig()->saveConfig('cmgroep_idin/services/18y_or_older', 0);
        }

        /**
         * Disable unavailable services
         */
        if (Mage::getStoreConfig('cmgroep_idin/services/identity') == 0) {
            Mage::getConfig()->saveConfig('cmgroep_idin/login/active', 0);
        }

        if (Mage::getStoreConfig('cmgroep_idin/services/name') == 0 || Mage::getStoreConfig('cmgroep_idin/services/address') == 0) {
            Mage::getConfig()->saveConfig('cmgroep_idin/registration/active', 0);
        }

        if (Mage::getStoreConfig('cmgroep_idin/services/18y_or_older') == 0) {
            Mage::getConfig()->saveConfig('cmgroep_idin/age_verification/active', 0);
        }

        /**
         * Refresh the cache to reflect changes immediately
         */
        Mage::getConfig()->cleanCache();
    }

    /**
     * If the module is de-activated, hide the iDIN 18+ Product Attribute
     */
    private function updateIdinProductAttributes()
    {
        $moduleStatus = Mage::getStoreConfig('cmgroep_idin/common/active');

        if ($moduleStatus == 0) {
            $attributeApi = Mage::getModel('catalog/product_attribute_api');
            $attributeData = array('is_visible' => false);
            $attributeApi->update('idin_require_age_verification', $attributeData);
        } else {
            $attributeApi = Mage::getModel('catalog/product_attribute_api');
            $attributeData = array('is_visible' => true);
            $attributeApi->update('idin_require_age_verification', $attributeData);
        }
    }

    /**
     * On config initialisation, add frontend classes for available services
     *
     * @param $event
     */
    public function updateAvailableServices($event)
    {
        /** @var Mage_Core_Model_Config_Base $config */
        $config = $event->getConfig();

        /** @var Mage_Core_Model_Config_Element $idinGroups */
        $idinGroups = $config->getNode('sections/cmgroep_idin/groups')->children();

        /** @var Mage_Core_Model_Config_Element $idinGroup */
        foreach($idinGroups as $idinGroup) {
            if ($idinGroup->required_services) {
                $frontendClass = 'available-idin-service';
                $requiredServices = explode(',', $idinGroup->required_services);

                foreach ($requiredServices as $requiredService) {
                    if (Mage::getStoreConfig('cmgroep_idin/services/' . $requiredService) == 0) {
                        $frontendClass = 'unavailable-idin-service';
                        break;
                    }
                }

                $idinGroup->frontend_class = $frontendClass;
            }
        }
    }
}