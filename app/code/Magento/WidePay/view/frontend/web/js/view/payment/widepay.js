define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'magento_widepay',
                component: 'Magento_WidePay/js/view/payment/method-renderer/widepay'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });
