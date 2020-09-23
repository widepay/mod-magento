<?php

namespace Magento\WidePay\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;

class TaxType implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => 'Sem alteração'],
            ['value' => '1', 'label' => 'Acrécimo em %'],
            ['value' => '2', 'label' => 'Acrécimo valor fixo em R$'],
            ['value' => '3', 'label' => 'Desconto em %'],
            ['value' => '4', 'label' => 'Desconto valor fixo em R$'],
        ];
    }
}
