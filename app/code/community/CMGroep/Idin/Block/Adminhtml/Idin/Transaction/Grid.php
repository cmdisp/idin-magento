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

class CMGroep_Idin_Block_Adminhtml_Idin_Transaction_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * CMGroep_Idin_Block_Adminhtml_Idin_Transaction_Grid constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setId('cmgroep_idin_transaction_grid');
        $this->setDefaultSort('transaction_date');
        $this->setDefaultDir('desc');
    }

    /**
     * Prepare transaction collection
     *  - Joins order table
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $transactionCollection = Mage::getResourceModel('cmgroep_idin/transaction_collection');

        /**
         * Join order table
         */
        $transactionCollection->joinOrderTable();

        $this->setCollection($transactionCollection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            array(
                'header' => $this->__('#'),
                'width' => '100px',
                'index' => 'entity_id'
            )
        );

        $this->addColumn(
            'transaction_date',
            array(
                'header' => $this->__('Date'),
                'width' => '150px',
                'type' => 'datetime',
                'index' => 'transaction_date'
            )
        );

        $this->addColumn(
            'transaction_id',
            array(
                'header' => $this->__('Transaction #'),
                'index' => 'transaction_id'
            )
        );

        $this->addColumn(
            'customer_id',
            array(
                'header' => $this->__('Customer #'),
                'index' => 'customer_id'
            )
        );

        $this->addColumn(
            'order_increment_id',
            array(
                'header' => $this->__('Order #'),
                'index' => 'order_increment_id'
            )
        );

        return parent::_prepareColumns();
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
        return $this->getUrl('adminhtml/idin_transaction/details', array('id' => $item->getId()));
    }
}