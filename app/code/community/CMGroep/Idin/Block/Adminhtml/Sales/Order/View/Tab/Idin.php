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

class CMGroep_Idin_Block_Adminhtml_Sales_Order_View_Tab_Idin extends Mage_Adminhtml_Block_Widget_Grid implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Retrieve collection of transactions for current order
     *
     * @return $this
     */
    public function _prepareCollection()
    {
        if ($this->getCollection() == null) {
            $currentOrder = Mage::registry('current_order');

            $collection = Mage::getResourceModel('cmgroep_idin/transaction_collection')
                ->addFieldToFilter('quote_id', array('eq' => $currentOrder->getQuoteId()));

            $this->setCollection($collection);

            return $this;
        }
    }

    /*
     * Render the columns
     */
    public function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header' => $this->__('#'),
            'width' => '100px',
            'index' => 'entity_id'
        ));

        $this->addColumn('transaction_date', array(
            'header' => $this->__('Date'),
            'width' => '150px',
            'type' => 'datetime',
            'index' => 'transaction_date'
        ));

        $this->addColumn('transaction_id', array(
            'header' => $this->__('Transaction #'),
            'index' => 'transaction_id'
        ));

        return $this;
    }

    /**
     * Return Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return $this->__('iDIN Transactions');
    }

    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->__('iDIN Transactions');
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        /** Make sure the collection has initialized */
        $this->_prepareCollection();

        return $this->getCollection()->count() > 0;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        /** Make sure the collection has initialized */
        $this->_prepareCollection();

        return $this->getCollection()->count() == 0;
    }

    /**
     * Retrieves detail url
     *
     * @param CMGroep_Idin_Model_Transaction $item
     *
     * @return string
     */
    public function getRowUrl($item)
    {
        return $this->getUrl('adminhtml/idin_transaction/details', ['id' => $item->getId()]);
    }
}