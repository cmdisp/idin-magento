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

class CMGroep_Idin_Helper_Crypto extends Mage_Core_Helper_Abstract
{

    /**
     * Generates a secure as possible random string
     *  - PHP 7: random_bytes
     *  - Mcrypt
     *  - OpenSSL
     *  - mt_rand
     *
     * @param int $length
     *
     * @return string
     */
    public function randomString($length = 40)
    {
        /**
         * PHP 7 only, use random_bytes
         */
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        }

        if (extension_loaded('mcrypt')) {
            return bin2hex(mcrypt_create_iv($length / 2, MCRYPT_DEV_URANDOM));
        } else if (extension_loaded('openssl')) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        } else {
            $randomString = '';
            $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
            $max = count($characters) - 1;

            for ($i = 0; $i < $length; $i++) {
                $rand = mt_rand(0, $max);
                $randomString .= $characters[$rand];
            }

            return $randomString;
        }
    }
}