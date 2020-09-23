<?php

namespace Magento\WidePay\Helper;

use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    protected $storeManager;
    protected $scopeConfig;

    public function __construct(StoreManagerInterface $storeManager, ScopeConfigInterface $scopeConfig){
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    public function getApiUrl(){
        return 'https://api.widepay.com/v1/';
    }

    public function getWalletId(){
        return trim($this->scopeConfig->getValue('payment/magento_widepay/WIDE_PAY_WALLET_ID', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE));
    }

    public function getWalletToken(){
        return trim($this->scopeConfig->getValue('payment/magento_widepay/WIDE_PAY_WALLET_TOKEN', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE));
    }

    public function getConfig($config){
        return trim($this->scopeConfig->getValue('payment/magento_widepay/' . $config, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE));
    }

    public function getNotificationUrl(){
        return trim($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) . 'widepay/order/update');
    }

}
