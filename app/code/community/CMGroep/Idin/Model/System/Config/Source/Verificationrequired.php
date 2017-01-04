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

class CMGroep_Idin_Model_System_Config_Source_Verificationrequired
{
    const MODE_ALWAYS = 1;
    const MODE_PRODUCTS = 2;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::MODE_ALWAYS, 'label' => Mage::helper('cmgroep_idin')->__('Always')),
            array('value' => self::MODE_PRODUCTS, 'label' => Mage::helper('cmgroep_idin')->__('Only 18+ products')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            self::MODE_ALWAYS => Mage::helper('cmgroep_idin')->__('Always'),
            self::MODE_PRODUCTS => Mage::helper('cmgroep_idin')->__('Only 18+ products'),
        );
    }
}