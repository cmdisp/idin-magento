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

require_once(Mage::getBaseDir('lib') . DS . 'CMGroep' . DS . 'Idin' . DS . 'autoload.php');

class CMGroep_Idin_Helper_Api extends Mage_Core_Helper_Abstract
{
    /**
     * Retrieves the host to be used depending on the extension mode
     *
     * @return string
     */
    public function getApiHost()
    {
        if (Mage::helper('cmgroep_idin')->getExtensionMode() == CMGroep_Idin_Model_System_Config_Source_Mode::MODE_LIVE) {
            return CMGroep_Idin_Model_System_Config_Source_Mode::GATEWAY_LIVE;
        } else {
            return CMGroep_Idin_Model_System_Config_Source_Mode::GATEWAY_TEST;
        }
    }

    /**
     * Retrieves API client with configuration according
     * to Magento System Configuration
     *
     * @return \CMGroep\Idin\ApiClient
     */
    public function getApiClient()
    {
        $apiConfiguration = new \CMGroep\Idin\Configuration();
        $apiConfiguration->setHost($this->getApiHost());
        $apiConfiguration->setUserAgent(sprintf('Magento-CMGroep-iDIN/%s', Mage::helper('cmgroep_idin')->getExtensionVersion()));

        $apiClient = new \CMGroep\Idin\ApiClient($apiConfiguration);

        return $apiClient;
    }

    /**
     * @return \CMGroep\Idin\Api\IdinApi
     */
    public function getIdinApi()
    {
        $idinApi = new \CMGroep\Idin\Api\IdinApi($this->getApiClient());

        return $idinApi;
    }

    /**
     * Prepares request by adding the required merchant token
     *
     * @param \CMGroep\Idin\Models\BaseRequest $baseRequest
     *
     * @return \CMGroep\Idin\Models\BaseRequest
     */
    public function prepareRequest(\CMGroep\Idin\Models\BaseRequest $baseRequest)
    {
        $merchantToken = Mage::helper('cmgroep_idin')->getMerchantToken();
        $baseRequest->setMerchantToken($merchantToken);

        return $baseRequest;
    }

    /**
     * Retrieves all issuers from the iDIN directory
     *
     * @return \CMGroep\Idin\Models\DirectoryResponse[]
     */
    public function getDirectory()
    {
        $api = $this->getIdinApi();

        /** @var \CMGroep\Idin\Models\DirectoryRequest $directoryRequest */
        $directoryRequest = $this->prepareRequest(new \CMGroep\Idin\Models\DirectoryRequest());
        $directoryResponse = $api->directoryPost($directoryRequest);

        return $directoryResponse;
    }

    /**
     * Retrieves merchant information
     *
     * @param $merchantToken
     *
     * @return \CMGroep\Idin\Models\MerchantResponse
     */
    public function getMerchantInformation($merchantToken)
    {
        $api = $this->getIdinApi();
        $merchantResponse = $api->merchantsMerchantTokenGet($merchantToken);

        return $merchantResponse;
    }

    /**
     * Retrieves the transaction status for a transaction
     *
     * @param $transactionId
     *
     * @return \CMGroep\Idin\Models\StatusResponse
     */
    public function getTransactionStatus($transactionId)
    {
        $api = $this->getIdinApi();

        /** @var \CMGroep\Idin\Models\StatusRequest $transactionStatusRequest */
        $transactionStatusRequest = $this->prepareRequest(new \CMGroep\Idin\Models\StatusRequest());
        $transactionStatusRequest->setTransactionId($transactionId);

        $transactionStatusResponse = $api->statusPost($transactionStatusRequest);

        return $transactionStatusResponse;
    }

    /**
     * Generates a random entrance code for a transaction
     *
     * @return string
     */
    public function generateEntranceCode()
    {
        $entranceCode = '';
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;

        for ($i = 0; $i < 40; $i++) {
            $rand = mt_rand(0, $max);
            $entranceCode .= $characters[$rand];
        }

        return $entranceCode;
    }

    /**
     * Serializes a status response for saving it in database
     *
     * @param \CMGroep\Idin\Models\StatusResponse $statusReponse
     *
     * @return string
     */
    public function serializeStatusResponse(\CMGroep\Idin\Models\StatusResponse $statusReponse)
    {
        return json_encode(\CMGroep\Idin\ObjectSerializer::sanitizeForSerialization($statusReponse));
    }

    /**
     * Deserializes a status response from the database
     *
     * @param $statusResponseData
     *
     * @return object
     */
    public function deserializeStatusResponse($statusResponseData)
    {
        return \CMGroep\Idin\ObjectSerializer::deserialize(
            json_decode($statusResponseData),
            \CMGroep\Idin\Models\StatusResponse::class
        );
    }
}