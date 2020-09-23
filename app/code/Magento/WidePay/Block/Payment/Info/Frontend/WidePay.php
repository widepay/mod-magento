<?php

namespace Magento\WidePay\Block\Payment\Info\Frontend;

class WidePay extends \Magento\Checkout\Block\Onepage\Success
{

    public function getOrder() {
        return $this->_checkoutSession->getLastRealOrder();
    }

}
