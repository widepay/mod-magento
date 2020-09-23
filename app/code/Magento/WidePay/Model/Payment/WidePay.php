<?php

namespace Magento\WidePay\Model\Payment;

class WidePay extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'magento_widepay';
    protected $_supportedCurrencyCodes = ['BRL'];
    protected $_canOrder = true;
    protected $_canCapture = true;
    protected $_canAuthorize = true;

    protected $_infoBlockType = 'Magento\WidePay\Block\Payment\Info\WidePay';

    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($this->canOrder()) {
            $info = $this->getInfoInstance();

            $order = $payment->getOrder();
            $data = $this->helper()->createOrderArray($order, $payment);

            if (!$data['error']) {
                $generate = $this->helper()->generate($data['data']);
                if (isset($generate['success']) && $generate['success']) {
                    $this->helper()->addInformation($order, $generate['additional']);
                } elseif ($generate['message']) {
                    $this->log('Erro ao else if; ' . $generate['message']);
                    throw new \Magento\Framework\Exception\CouldNotSaveException(
                        __($generate['message'])
                    );
                } else {
                    $this->log('Erro não identificado');
                    throw new \Magento\Framework\Exception\CouldNotSaveException(
                        __('Erro não identificado')
                    );
                }
            } else {
                $message = isset($data['error_message']) ? $data['error_message'] : 'Erro ao gerar boleto.';
                throw new \Magento\Framework\Exception\CouldNotSaveException(
                    __($message)
                );
            }
        } else {
            $this->log('Não entrou no canOrder');
        }
    }

    protected function helper()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\WidePay\Helper\Order');
    }

    protected function log($msg)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/widepay.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
