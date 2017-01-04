var IdinCheckout = Class.create(Checkout, {
    initialize: function($super, accordion, urls, checkoutMethod) {
        $super(accordion, urls);
        this.steps = ['login', 'age_verification', 'billing', 'shipping', 'shipping_method', 'payment', 'review'];
        this.checkoutMethod = checkoutMethod;

        document.observe('login:setMethod', function(event) {
            if(event.memo.method != '') {
                if(event.memo.method == 'customer') {
                    checkout.accordion.closeSection('opc-billing');
                } else {
                    checkout.accordion.closeSection('opc-login');
                }
                checkout.gotoSection('age_verification', true);
            }
        });

        if(this.checkoutMethod != '') {
            this.currentStep = 'age_verification';

            if(this.checkoutMethod != 'customer') {
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
            checkout.gotoSection('billing', false);
            document.body.fire('login:setMethod', {method : this.checkoutMethod});
        }
    }
});