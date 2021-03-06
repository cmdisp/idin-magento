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
var IdinAgeVerification = Class.create();
IdinAgeVerification.prototype = {
    initialize: function(form, verifyUrl) {
        this.form = form;
        this.verifyUrl = verifyUrl;

        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    /**
     * Starts the Age Verification Transaction
     */
    start: function() {
        if ($('checkout_method').value != 'customer') {
            $('checkout_method').value = checkout.method;
        }

        var form = new VarienForm(this.form);
        if (form.validator && form.validator.validate()) {
            $(this.form).submit();
        }
    },

    /**
     * Calls the verify action and continues the checkout
     */
    save: function() {
        if (checkout.loadWaiting != false) return;

        checkout.setLoadWaiting('age_verification');

        new Ajax.Request(
            this.verifyUrl,
            {
                method: 'post',
                onComplete: this.onComplete,
                onSuccess: this.onSave,
                onFailure: checkout.ajaxFailure.bind(checkout)
            }
        );
    },

    /**
     * Parses the response from the verify action and triggers the next
     * checkout step
     *
     * @param transport
     */
    nextStep: function(transport) {
        var response = transport.responseJSON || transport.responseText.evalJSON(true) || {};

        if (response.error){
            if (Object.isString(response.message)) {
                alert(response.message.stripTags().toString());
            }
        }

        checkout.setStepResponse(response);
    },

    /**
     * Resets the step loading indicator
     *
     * @param transport
     */
    resetLoadWaiting: function(transport) {
        checkout.setLoadWaiting(false);
        document.body.fire('age_verification-request:completed', {transport: transport});
    }
};