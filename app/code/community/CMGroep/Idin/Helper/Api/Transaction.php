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

include(Mage::getBaseDir('lib') . DS . 'CMGroep' . DS . 'Idin' . DS . 'autoload.php');

class CMGroep_Idin_Helper_Api_Transaction extends Mage_Core_Helper_Abstract
{

    /**
     * @var CMGroep_Idin_Helper_Api
     */
    protected $_apiHelper = null;

    /**
     * @var \CMGroep\Idin\Api\IdinApi
     */
    protected $_idinApi = null;

    /**
     * @var \CMGroep\Idin\Models\TransactionRequest
     */
    protected $_transactionRequest = null;

    /**
     * CMGroep_Idin_Helper_Api_Transaction constructor.
     */
    public function __construct()
    {
        if($this->_apiHelper == null) {
            $this->_apiHelper = Mage::helper('cmgroep_idin/api');
        }

        if($this->_idinApi == null) {
            $this->_idinApi = $this->_apiHelper->getIdinApi();
        }
    }

    /**
     * Starts a new transaction
     *
     * @param $issuerId Issuer ID of bank
     * @param $entranceCode Entrance Code
     * @param $returnUrl Return url
     *
     * @return $this
     */
    public function start($issuerId, $entranceCode, $returnUrl)
    {
        $this->_transactionRequest = $this->_apiHelper->prepareRequest(new \CMGroep\Idin\Models\TransactionRequest());
        $this->_transactionRequest->setIssuerId($issuerId);
        $this->_transactionRequest->setEntranceCode($entranceCode);

        $this->_transactionRequest->setMerchantReturnUrl($returnUrl);

        return $this;
    }

    /**
     * Executes the current transaction
     *
     * @return \CMGroep\Idin\Models\TransactionResponse
     */
    public function execute()
    {
        $transactionResponse = $this->_idinApi->transactionPost($this->_transactionRequest);

        return $transactionResponse;
    }

    /**
     * Retrieve identity with transaction
     *
     * @return $this
     */
    public function withIdentity()
    {
        $this->_transactionRequest->setIdentity(true);

        return $this;
    }

    /**
     * Retrieve name with transaction
     *
     * @return $this
     */
    public function withName()
    {
        $this->_transactionRequest->setName(true);

        return $this;
    }

    /**
     * Retrieve address with transaction
     *
     * @return $this
     */
    public function withAddress()
    {
        $this->_transactionRequest->setAddress(true);

        return $this;
    }

    /**
     * Retrieve date of birth with transaction
     *
     * @return $this
     */
    public function withDateOfBirth()
    {
        $this->_transactionRequest->setDateOfBirth(true);

        return $this;
    }

    /**
     * Retrieve 18 year or older status with transaction
     *
     * @return $this
     */
    public function with18yOrOlder()
    {
        $this->_transactionRequest->set18yOrOlder(true);

        return $this;
    }
}