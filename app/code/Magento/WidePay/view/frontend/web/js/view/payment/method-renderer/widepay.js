define([
        'jquery',
        'Magento_Checkout/js/view/payment/default'
    ],
    function ($, Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Magento_WidePay/payment/widepay',
                widepay_taxvat: '',
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'widepay_taxvat',
                    ]);

                return this;
            },
            context: function() {
                return this;
            },
            getCode: function() {
                return 'magento_widepay';
            },
            isActive: function() {
                return true;
            },
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'widepay_taxvat': $('input#' + this.getCode() + '_taxvat').val()
                    }
                }
            }
        });
    }
);

