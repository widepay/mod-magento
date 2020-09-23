<?php

namespace Magento\WidePay\Controller\Order;

class Update extends \Magento\Framework\App\Action\Action
{
    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\EncoderInterface $encoder,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory $creditmemoFactory
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;

        if ($context->getRequest()->getMethod() == 'POST') {
            $key_form = $this->_objectManager->get('Magento\Framework\Data\Form\FormKey');
            $context->getRequest()->setParam('form_key', $key_form->getFormKey());
        }

        $this->_context = $context;
        $this->_pageFactory = $pageFactory;
        $this->_jsonEncoder = $encoder;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["notificacao"])) {
            $notificacao = $this->api(intval($this->helper()->getConfig('WIDE_PAY_WALLET_ID')), trim($this->helper()->getConfig('WIDE_PAY_WALLET_TOKEN')), 'recebimentos/cobrancas/notificacao', [
                'id' => $_POST["notificacao"] // ID da notificação recebido do Wide Pay via POST
            ]);
            if ($notificacao->sucesso) {
                $order_id = (int)$notificacao->cobranca['referencia'];
                $transactionID = $notificacao->cobranca['id'];
                $status = $notificacao->cobranca['status'];
                if ($status == 'Baixado' || $status == 'Recebido' || $status == 'Recebido manualmente') {
                    $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($order_id);
                    if ($order->canInvoice()) {
                        $invoice = $this->_invoiceService->prepareInvoice($order);
                        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                        $invoice->register();
                        $invoice->getOrder()->setCustomerNoteNotify(false);
                        $invoice->getOrder()->setIsInProcess(true);
                        $order->addStatusHistoryComment('Pedido aprovado através do Wide Pay.', 'processing');
                        $transactionSave = $this->_transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
                        $transactionSave->save();
                    }

                }
            } else {
                $this->log('Erro de notificação: ' . $notificacao->erro);
                echo $notificacao->erro; // Erro
                exit();
            }
        }
        exit();
    }

    private function api($wallet, $token, $local, $params = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.widepay.com/v1/' . trim($local, '/'));
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

    protected function helper()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\WidePay\Helper\Data');
    }

    private function log($msg)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/widepay.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
