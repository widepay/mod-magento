<?php

namespace Magento\WidePay\Block\Payment\Info;

use Magento\Payment\Block\Info;
use Magento\Framework\DataObject;

class WidePay extends Info
{
    const TEMPLATE = 'Magento_WidePay::info/widepay.phtml';

    public function _construct()
    {
        $this->setTemplate(self::TEMPLATE);
    }

    public function getTitle()
    {
        return $this->getInfo()->getMethodInstance()->getTitle();
    }

}
