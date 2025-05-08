/*
 * All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        Component,
        rendererList
    ) {
        'use strict';
        var defaultComponent = 'GingerPay_Payment/js/view/payment/method-renderer/default';
        var idealComponent = 'GingerPay_Payment/js/view/payment/method-renderer/ideal';
        var methods = [
            {type: 'ginger_methods_creditcard', component: defaultComponent},
            {type: 'ginger_methods_applepay', component: defaultComponent},
            {type: 'ginger_methods_googlepay', component: defaultComponent},
            {type: 'ginger_methods_mobilepay', component: defaultComponent},
            {type: 'ginger_methods_swish', component: defaultComponent}
        ];
        $.each(methods, function (k, method) {
            var paymentMethod = window.checkoutConfig.payment[method['type']];

            if (paymentMethod.isActive)
            {
                if (method.type == 'ginger_methods_applepay')
                {
                    if (window.ApplePaySession && paymentMethod.isActive)
                    {
                        rendererList.push(method);
                    }
                }
                else
                {
                    rendererList.push(method);
                }
            }

        });
        return Component.extend({});
    }
);
