<?php

namespace Magento\WidePay\Helper;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\OrderFactory;

class Order extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $orderFactory;
    protected $request;

    public function __construct(OrderFactory $orderFactory, RequestInterface $request)
    {
        $this->orderFactory = $orderFactory;
        $this->request = $request;
    }

    public function createOrderArray($order, $payment)
    {
        if ($order && $order->getRealOrderId()) {
            $address = $order->getBillingAddress();

            $taxvat = ($payment->getAdditionalInformation('widepay_taxvat') != '') ? $payment->getAdditionalInformation('widepay_taxvat') : $order->getCustomerTaxvat();

            if (!$taxvat) {
                if (isset($this->request->getPost()['payment']['magento_widepay_taxvat']) && $this->request->getPost()['payment']['magento_widepay_taxvat'] != '') {
                    $taxvat = $this->request->getPost()['payment']['magento_widepay_taxvat'];
                }
            }
            if (!$this->validateTaxvat($taxvat)) {
                return ['error' => true, 'error_message' => 'CPF/CNPJ inválido.'];
            }

            $name = $address->getFirstname() . ' ' . $address->getLastname();

            $total = $order->getGrandTotal();
            $frete = $order->getShippingAmount();
            $produtos_array = $order->getItems();
            $desconto = $order->getDiscountAmount();
            $endereco = $order->getBillingAddress();

            $tax = $this->helper()->getConfig('WIDE_PAY_TAX_VARIATION');
            $tax_type = $this->helper()->getConfig('WIDE_PAY_TAX_TYPE');

            //produtos
            $items = [];
            $i = 1;
            foreach ($produtos_array as $item) {
                $items[$i]['descricao'] = $item->getName();
                $items[$i]['valor'] = number_format($item->getPrice(), 2, '.', '');
                $items[$i]['quantidade'] = $item->getQtyOrdered();
                $i++;
            }
            if (isset($frete) && $frete > 0) {
                $items[$i]['descricao'] = 'Frete';
                $items[$i]['valor'] = number_format($frete, 2, '.', '');
                $items[$i]['quantidade'] = 1;
                $i++;
            }
            if (isset($desconto) && $desconto > 0) {
                $items[$i]['descricao'] = 'Desconto';
                $items[$i]['valor'] = number_format($desconto, 2, '.', '') * (-1);
                $items[$i]['quantidade'] = 1;
                $i++;
            }
            $variableTax = $this->getVariableTax($tax, $tax_type, $total);
            if (isset($variableTax)) {
                list($description, $total) = $variableTax;
                $items[$i]['descricao'] = $description;
                $items[$i]['valor'] = $total;
                $items[$i]['quantidade'] = 1;
            }

            $invoiceDuedate = new \DateTime(date('Y-m-d'));
            $invoiceDuedate->modify('+' . intval($this->helper()->getConfig('WIDE_PAY_VALIDADE')) . ' day');
            $invoiceDuedate = $invoiceDuedate->format('Y-m-d');
            $tel = $endereco->getTelephone();
            $tel = str_replace('+55', '', $tel);
            $fiscal = preg_replace('/\D/', '', $taxvat);
            list($widepayCpf, $widepayCnpj, $widepayPessoa) = $this->getFiscal($fiscal);

            $widepayData = [
                'forma' => $this->widepay_get_formatted_way($this->helper()->getConfig('WIDE_PAY_WAY')),
                'referencia' => $order->getIncrementId(),
                'notificacao' => $this->helper()->getNotificationUrl(),
                'vencimento' => $invoiceDuedate,
                'cliente' => (preg_replace('/\s+/', ' ', $name)),
                'telefone' => preg_replace('/\D/', '', $tel),
                'email' => $order->getCustomerEmail(),
                'pessoa' => $widepayPessoa,
                'cpf' => $widepayCpf,
                'cnpj' => $widepayCnpj,
                'enviar' => 'E-mail',
                'endereco' => [
                    'rua' => (isset($endereco->getStreet()[0])) ? $endereco->getStreet()[0] : '',
                    'complemento' => (isset($endereco->getStreet()[2])) ? $endereco->getStreet()[2] : '',
                    'cep' => preg_replace('/\D/', '', $endereco->getPostcode()),
                    'estado' => (isset($endereco->getStreet()[3])) ? $endereco->getStreet()[3] : '',
                    'cidade' => $endereco->getCity()
                ],
                'itens' => $items,
                'boleto' => [
                    'gerar' => 'Nao',
                    'desconto' => 0,
                    'multa' => doubleval($this->helper()->getConfig('WIDE_PAY_FINE')),
                    'juros' => doubleval($this->helper()->getConfig('WIDE_PAY_INTEREST'))
                ]];

            return ['error' => false, 'data' => $widepayData];
        } else {
            return ['error' => true, 'error_message' => 'O pedido não foi gerado'];
        }
    }

    public function validateTaxvat($taxvat)
    {
        $taxvat = str_replace(['-', '.'], '', $taxvat);
        //Caso seja CNPJ
        if (strlen($taxvat) == 14) {
            return $this->validateCnpj($taxvat);
        }

        //Caso seja CPF
        if (strlen($taxvat) == 11) {
            return $this->validateCpf($taxvat);
        }
    }

    public function generate($data)
    {
        $response = $this->api(intval($this->helper()->getConfig('WIDE_PAY_WALLET_ID')), $this->helper()->getConfig('WIDE_PAY_WALLET_TOKEN'), 'recebimentos/cobrancas/adicionar', $data);

        if (!$response->sucesso) {
            $validacao = '';

            if ($response->erro) {
                $validacao = $response->erro . '<br>';
            }

            if (isset($response->validacao)) {
                foreach ($response->validacao as $item) {
                    $validacao .= '- ' . strtoupper($item['id']) . ': ' . $item['erro'] . '<br>';
                }
                $validacao = 'Erro Validação: ' . $validacao;
            }

            $this->log('Erro ao gerar boleto #' . $data['referencia'] . ': ' . $validacao);
            return ['success' => false, 'message' => $validacao];
        } else {
            $additional = [
                'boleto_url' => $response->link,
                'linha_digitavel' => isset($response->boleto['linha-digitavel']) ? $response->boleto['linha-digitavel'] : 'Boleto não gerado',
                'vencimento' => $data['vencimento']
            ];
            return ['success' => true, 'additional' => $additional];
        }
    }

    private function api($wallet, $token, $local, $params = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->helper()->getApiUrl() . trim($local, '/'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, trim($wallet) . ':' . trim($token));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['WP-API: SDK-PHP']);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        $exec = curl_exec($curl);
        curl_close($curl);
        if ($exec) {
            $requisicao = json_decode($exec, true);
            if (!is_array($requisicao)) {
                $requisicao = [
                    'sucesso' => false,
                    'erro' => 'Não foi possível tratar o retorno.'
                ];
                if ($exec) {
                    $requisicao['retorno'] = $exec;
                }
            }
        } else {
            $requisicao = [
                'sucesso' => false,
                'erro' => 'Sem comunicação com o servidor.'
            ];
        }

        return (object)$requisicao;
    }

    public function addInformation($order, $additional)
    {
        if ($order && is_array($additional) && count($additional) >= 1) {
            $_additional = $order->getPayment()->getAdditionalInformation();
            foreach ($additional as $key => $value) {
                $_additional[$key] = $value;
            }
            $this->log($_additional);
            $order->getPayment()->setAdditionalInformation($_additional);
        } else {
            $this->log('Problema no IF');
            $this->log(var_export($additional));
        }
    }

    protected function helper()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\WidePay\Helper\Data');
    }

    private function validateCpf($taxvat)
    {
        if (empty($taxvat)) {
            return false;
        }

        $taxvat = preg_replace('#[^0-9]#', '', $taxvat);
        $taxvat = str_pad($taxvat, 11, '0', STR_PAD_LEFT);

        if (strlen($taxvat) != 11) {
            return false;
        }

        if ($taxvat == '00000000000' ||
            $taxvat == '11111111111' ||
            $taxvat == '22222222222' ||
            $taxvat == '33333333333' ||
            $taxvat == '44444444444' ||
            $taxvat == '55555555555' ||
            $taxvat == '66666666666' ||
            $taxvat == '77777777777' ||
            $taxvat == '88888888888' ||
            $taxvat == '99999999999'
        ) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $taxvat{$c} * (($t + 1) - $c);
            }

            $d = ((10 * $d) % 11) % 10;

            if ($taxvat{$c} != $d) {
                return false;
            }
        }

        return true;
    }

    private function validateCnpj($taxvat)
    {
        $taxvat = preg_replace('/[^0-9]/', '', (string)$taxvat);

        if (strlen($taxvat) != 14) {
            return false;
        }

        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $taxvat{$i} * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        if ($taxvat{12} != ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }

        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $taxvat{$i} * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        return $taxvat{13} == ($resto < 2 ? 0 : 11 - $resto);
    }


    private function getVariableTax($tax, $taxType, $total)
    {
        //Formatação para calculo ou exibição na descrição
        $widepayTaxDouble = number_format((double)$tax, 2, '.', '');
        $widepayTaxReal = number_format((double)$tax, 2, ',', '');
        // ['Description', 'Value'] || Null

        if ($taxType == 1) {//Acrécimo em Porcentagem
            return array(
                'Referente a taxa adicional de ' . $widepayTaxReal . '%',
                round((((double)$widepayTaxDouble / 100) * $total), 2));
        } elseif ($taxType == 2) {//Acrécimo valor Fixo
            return array(
                'Referente a taxa adicional de R$' . $widepayTaxReal,
                ((double)$widepayTaxDouble));
        } elseif ($taxType == 3) {//Desconto em Porcentagem
            return array(
                'Item referente ao desconto: ' . $widepayTaxReal . '%',
                round((((double)$widepayTaxDouble / 100) * $total), 2) * (-1));
        } elseif ($taxType == 4) {//Desconto valor Fixo
            return array(
                'Item referente ao desconto: R$' . $widepayTaxReal,
                $widepayTaxDouble * (-1));
        }
        return null;
    }

    private function widepay_get_formatted_way($way)
    {
        $key_value = array(
            'cartao' => 'Cartão',
            'boleto' => 'Boleto',
            'boleto_cartao' => 'Cartão,Boleto',

        );
        return $key_value[$way];
    }

    private function getFiscal($cpf_cnpj)
    {
        $cpf_cnpj = preg_replace('/\D/', '', $cpf_cnpj);
        // [CPF, CNPJ, FISICA/JURIDICA]
        if (strlen($cpf_cnpj) == 11) {
            return array($cpf_cnpj, '', 'Física');
        } else {
            return array('', $cpf_cnpj, 'Jurídica');
        }
    }

    private function log($msg)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/widepay.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
