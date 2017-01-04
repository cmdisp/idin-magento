var IdinAgeVerification = Class.create();
IdinAgeVerification.prototype = {
    initialize: function(form, verifyUrl) {
        this.form = form;
        this.verifyUrl = verifyUrl;

        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    start: function() {
        if ($('checkout_method').value != 'customer') {
            $('checkout_method').value = checkout.method;
        }

        $(this.form).submit();
    },

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

    nextStep: function(transport) {
        var response = transport.responseJSON || transport.responseText.evalJSON(true) || {};

        if (response.error){
            if (Object.isString(response.message)) {
                alert(response.message.stripTags().toString());
            }
        }

        checkout.setStepResponse(response);
    },

    resetLoadWaiting: function(transport) {
        checkout.setLoadWaiting(false);
        document.body.fire('age_verification-request:completed', {transport: transport});
    }
};