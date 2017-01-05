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
var IdinCheckout = Class.create(Checkout, {
    initialize: function($super, accordion, urls, checkoutMethod) {
        $super(accordion, urls);
        this.steps = ['login', 'age_verification', 'billing', 'shipping', 'shipping_method', 'payment', 'review'];
        this.checkoutMethod = checkoutMethod;

        /**
         * Overwrites the default action after specifying a login method
         * Normally it would trigger the billing step, this makes sure the customer
         * is redirected to the age verification step
         */
        document.observe('login:setMethod', function(event) {
            if (event.memo.method != '') {
                if (event.memo.method == 'customer') {
                    checkout.accordion.closeSection('opc-billing');
                } else {
                    checkout.accordion.closeSection('opc-login');
                }
                checkout.gotoSection('age_verification', true);
            }
        });

        /**
         * After a age verification transaction is finished this makes sure
         * the checkout process is continued where the customer left off
         */
        if (this.checkoutMethod != '') {
            this.currentStep = 'age_verification';
            ageVerification.save();
            checkout.setLoadWaiting(false);

            if (this.checkoutMethod != 'customer') {
                checkout.method = this.checkoutMethod;
                new Ajax.Request(
                    checkout.saveMethodUrl,
                    {
                        method: 'post',
                        onFailure: checkout.ajaxFailure.bind(this),
                        parameters: {method: this.checkoutMethod}
                    }
                );

                if (this.checkoutMethod == 'guest') {
                    Element.hide('register-customer-password');
                } else {
                    Element.show('register-customer-password');
                }
            }

            accordion.currentSection = 'opc-age_verification';
            checkout.gotoSection('billing', true);
            //document.body.fire('login:setMethod', {method : this.checkoutMethod});
        }
    }
});