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

class CMGroep_Idin_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{

    /**
     * Creates table for pending registrations
     *
     * @return bool
     * @throws Zend_Db_Exception
     */
    public function createPendingRegistrationsTable()
    {
        $tableName = $this->getTable('cmgroep_idin/registration');
        $table = $this->getConnection()->newTable($tableName)
            ->addColumn('registration_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'primary' => true,
                'unsigned' => true,
                'identity' => true,
                'nullable' => false
            ), 'Registration Identifier')
            ->addColumn('entrance_code', Varien_Db_Ddl_Table::TYPE_TEXT, 40, array(
                'nullable' => false
            ), 'Entrance Code')
            ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
                'nullable' => false
            ), 'Transaction ID')
            ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'nullable' => true
            ), 'Customer ID for existing customers')
            ->addColumn('quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'nullable' => true
            ), 'Quote ID for guest checkout')
            ->addColumn('transaction_response', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
                'nullable' => true
            ), 'Transaction Response')
            ->addIndex(
                $this->getConnection()->getIndexName(
                    $tableName,
                    array('entrance_code', 'transaction_id')
                ), array('entrance_code', 'transaction_id'));

        $this->getConnection()->createTable($table);

        return true;
    }

    /**
     * Adds extra column to quote table for age verification
     *
     * @return bool
     */
    public function addExtraColumnsToQuoteTable()
    {
        $tableName = $this->getTable('sales/quote');
        $this->getConnection()->addColumn($tableName, 'idin_age_verified', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 1,
            'nullable' => false,
            'default' => 0,
            'comment' => 'iDIN Age Verification Status'
        ));

        return true;
    }
}