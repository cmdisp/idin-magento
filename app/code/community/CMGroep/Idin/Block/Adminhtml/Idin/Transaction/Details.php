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

class CMGroep_Idin_Block_Adminhtml_Idin_Transaction_Details extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * CMGroep_Idin_Block_Adminhtml_Idin_Transaction_Details constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'cmgroep_idin';
        $this->_controller = 'adminhtml_idin_transaction';
        $this->_mode = 'details';
        $this->_headerText = $this->__('Details for transaction %s', $this->getTransaction()->getTransactionId());

        $this->_removeButton('reset');
        $this->_removeButton('delete');
        $this->_removeButton('save');
    }

    /**
     * @return CMGroep_Idin_Model_Transaction
     */
    public function getTransaction()
    {
        if ($this->getRequest()->getParam('id')) {
            return Mage::getModel('cmgroep_idin/transaction')->load($this->getRequest()->getParam('id'));
        }
    }
}