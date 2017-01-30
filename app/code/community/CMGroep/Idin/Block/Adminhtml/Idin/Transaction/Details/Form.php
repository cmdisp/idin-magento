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

class CMGroep_Idin_Block_Adminhtml_Idin_Transaction_Details_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected $_transaction = null;

    /**
     * CMGroep_Idin_Block_Adminhtml_Idin_Transaction_Details_Form constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setId('cmgroep_idin_transaction_details_form');
    }

    /**
     * Render the transaction form
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'id' => 'detail_form'
            )
        );

        $transactionDetailsFieldset = $form->addFieldset(
            'transaction_details_fieldset',
            array(
                'legend' => $this->__('Transaction Information'),
                'class'  => 'fieldset'
            )
        );

        $transactionDetailsFieldset->addField(
            'transaction_date',
            'label',
            array(
                'label' => $this->__('Transaction Date'),
                'value' => Mage::helper('core')->formatDate(
                    $this->getTransaction()->getTransactionDate(), Mage_Core_Model_Locale::FORMAT_TYPE_LONG, true
                )
            )
        );

        $transactionDetailsFieldset->addField(
            'transaction_id',
            'label',
            array(
                'label' => $this->__('Transaction ID'),
                'value' => $this->getTransaction()->getTransactionId()
            )
        );

        $transactionDetailsFieldset->addField(
            'entrance_code',
            'label',
            array(
                'label' => $this->__('Entrance Code'),
                'value' => $this->getTransaction()->getEntranceCode()
            )
        );

        if ($this->getTransaction()->getCustomerId()) {
            $transactionDetailsFieldset->addField(
                'customer_id',
                'link',
                array(
                    'label' => $this->__('Customer #'),
                    'href' => $this->getUrl('adminhtml/customer/edit', array('id' => $this->getTransaction()->getCustomerId())),
                    'value' => $this->getTransaction()->getCustomerId()
                )
            );
        }

        if ($this->getTransaction()->getQuoteId()) {
            $orderCollection = Mage::getResourceModel('sales/order_collection')
                ->addAttributeToFilter('quote_id', $this->getTransaction()->getQuoteId())
                ->setPageSize(1);

            if ($orderCollection->getSize() > 0 && $order = $orderCollection->getFirstItem()) {
                $transactionDetailsFieldset->addField(
                    'order_id',
                    'link',
                    array(
                        'label' => $this->__('Order #'),
                        'href' => $this->getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId())),
                        'value' => $order->getIncrementId()
                    )
                );
            }
        }

        $transactionResponseFieldset = $form->addFieldset(
            'transaction_response_fieldset',
            array(
                'legend' => $this->__('Transaction Response'),
                'class'  => 'fieldset'
            )
        );

        $transactionResponseFieldset->addField(
            'status_response',
            'hidden',
            array(
                'name' => '',
                'after_element_html' => $this->getLayout()
                    ->createBlock('cmgroep_idin/adminhtml_idin_transaction_details_response')
                    ->setResponse($this->getTransaction()->getTransactionResponse())
                    ->toHtml()
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return CMGroep_Idin_Model_Transaction
     */
    public function getTransaction()
    {
        if ($this->_transaction == null) {
            if ($this->getRequest()->getParam('id')) {
                $this->_transaction = Mage::getModel('cmgroep_idin/transaction')->load(
                    $this->getRequest()->getParam('id')
                );
            }
        }

        return $this->_transaction;
    }
}