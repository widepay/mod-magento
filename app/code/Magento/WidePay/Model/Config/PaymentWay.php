<?php

namespace Magento\WidePay\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentWay implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'boleto_cartao', 'label' => 'Boleto e Cartão'],
            ['value' => 'boleto', 'label' => 'Boleto'],
            ['value' => 'cartao', 'label' => 'Cartão'],
        ];
    }
}
