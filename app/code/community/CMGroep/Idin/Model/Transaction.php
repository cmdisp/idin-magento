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

/**
 * @method int getRegistrationId()
 * @method CMGroep_Idin_Model_Transaction setRegistrationId(int $value)
 * @method string getTransactionDate()
 * @method CMGroep_Idin_Model_Transaction setTransactionDate(string $value)
 * @method string getEntranceCode()
 * @method CMGroep_Idin_Model_Transaction setEntranceCode(string $value)
 * @method string getTransactionId()
 * @method CMGroep_Idin_Model_Transaction setTransactionId(string $value)
 * @method string getCustomerId()
 * @method CMGroep_Idin_Model_Transaction setCustomerId(string $value)
 * @method string getQuoteId()
 * @method CMGroep_Idin_Model_Transaction setQuoteId(string $value)
 * @method string getTransactionResponse()
 * @method CMGroep_Idin_Model_Transaction setTransactionResponse(string $value)
 *
 * Class CMGroep_Idin_Model_Transaction
 */
class CMGroep_Idin_Model_Transaction extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('cmgroep_idin/transaction');
    }

    /**
     * Set transaction date before save
     *
     * @return CMGroep_Idin_Model_Transaction
     */
    public function _beforeSave()
    {
        if ($this->isObjectNew()) {
            $this->setTransactionDate(Varien_Date::now());
        }

        return parent::_beforeSave();
    }
}