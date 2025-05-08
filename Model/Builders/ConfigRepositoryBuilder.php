<?php

namespace GingerPay\Payment\Model\Builders;

require_once __DIR__.'/ApiBuilder.php';
require_once __DIR__.'/../../Api/Config/RepositoryInterface.php';

use GingerPay\Payment\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigRepositoryBuilder extends ApiBuilder implements ConfigRepositoryInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var PricingHelper
     */
    protected $pricingHelper;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var ErrorLogger
     */
    protected $errorLogger;

    /**
     * @var DebugLogger
     */
    protected $debugLogger;

    /**
     * {@inheritDoc}
     */
    public function isAvailable(int $storeId): bool
    {
        $active = $this->getStoreConfig(self::XML_PATH_MODULE_ACTIVE);
        if (!$active) {
            return false;
        }

        $apiKey = $this->getApiKey($storeId);
        if (!$apiKey) {
            return false;
        }

        return true;
    }

    /**
     * Get config value
     *
     * @param string $path
     * @param int $storeId
     *
     * @return string|array
     */
    protected function getStoreConfig(string $path, int $storeId = 0)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getApiKey(int $storeId): string
    {
        return $this->getStoreConfig(self::XML_PATH_APIKEY, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function useMethodCheck(): bool
    {
        return (bool)$this->getFlag(self::XML_PATH_OBSERVER);
    }

    /**
     * Get config flag
     *
     * @param string $path
     * @param int $storeId
     *
     * @return bool
     */
    protected function getFlag(string $path, int $storeId = 0): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, (int)$storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodCodeFromOrder(OrderInterface $order): string
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();
        return str_replace('ginger_methods_', '', $method);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusProcessing(string $method, int $storeId = 0): string
    {
        $path = 'payment/' . $method . '/order_status_processing';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusPending(string $method, int $storeId = 0): string
    {
        $path = 'payment/' . $method . '/order_status_pending';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusAuthorized(string $method, int $storeId = 0): string
    {
        $path = 'payment/' . $method . '/order_status_authorized';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function sendInvoice(string $method, int $storeId = 0): bool
    {
        $path = 'payment/' . $method . '/invoice_notify';
        return (bool)$this->getFlag($path, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription($order, $method): string
    {
        $storeId = (int)$order->getStoreId();

        $description = ($this->getStoreConfig($path = 'payment/' . $method . '/description', $storeId));
        $description = str_replace('%id%', $order->getIncrementId(), $description);

        $storeName = $this->getStoreConfig(self::XML_PATH_STORE_NAME, $storeId);
        $storeName = $storeName ?? __('our shop');
        $description = str_replace('%name%', $storeName, $description);

        return $description;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountDetails(): array
    {
        return $this->getStoreConfig(self::XML_PATH_ACCOUNT_DETAILS);
    }

    /**
     * {@inheritDoc}
     */
    public function getCompanyName(int $storeId): string
    {
        return (string)$this->getStoreConfig(self::XML_PATH_COMPANY_NAME, $storeId);
    }


    /**
     * {@inheritDoc}
     */
    public function getTestKey(string $method, int $storeId, string $testFlag = ''): string
    {
        return $this->getApiKey($storeId);
    }

    private function getTestApiKeyByPath($modusPath, $testKeyPath, $storeId = 0, $force = false)
    {
        $testModus = $this->getStoreConfig($modusPath, $storeId);

        $testApiKey = $this->getStoreConfig($testKeyPath, $storeId);

        if ((!$testModus && !$force) || empty($testApiKey)) {
            return null;
        }

        return $testApiKey;
    }

    /**
     * {@inheritDoc}
     */
    public function addTolog(string $type, $data)
    {
        if ($this->isDebugEnabled()) {
            if ($type == 'error') {
                $this->errorLogger->addLog($type, $data);
            } elseif ($this->isDebugEnabled()) {
                $this->debugLogger->addLog($type, $data);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isDebugEnabled(): bool
    {
        return (bool)$this->getFlag(self::XML_PATH_DEBUG);
    }

    /**
     * {@inheritDoc}
     */
    public function getPluginVersion(): string
    {
        return $this->getExtensionVersion();
    }

    /**
     * {@inheritDoc}
     */
    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getExtensionVersion(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_VERSION);
    }


    /**
     * {@inheritDoc}
     */
    public function getPaymentNameByMethodCode($methodCode): string
    {
        return $this->scopeConfig->getValue('payment/'.$methodCode.'/title');
    }

    /**
     * {@inheritDoc}
     */
    public function getError(array $transaction)
    {
        if ($transaction['status'] == 'error' && (current($transaction['transactions'])['customer_message']))
        {
            return current($transaction['transactions'])['customer_message'];
        }
        if (!empty($transaction['customer_messages']))
        {
            return current($transaction['customer_messages'])["message"];
        }
        return false;
    }

    /**
     * Returns method code without prefix
     *
     * @param string $method
     * @return string
     */
    protected function getShortMethodCode($method): string
    {
        return str_replace(self::METHOD_PREFIX, '', $method);
    }

    /**
     * {@inheritDoc}
     */
    public function getAmountInCents(float $amount): int
    {
        return (int)round($amount * 100);
    }

    /**
     * {@inheritDoc}
     */
    public function formatPrice(float $price)
    {
        return $this->pricingHelper->currency((float)$price, true, false);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentStoreId(): int
    {
        return (int)$this->getStore()->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getStore(): StoreInterface
    {
        try {
            return $this->storeManager->getStore();
        } catch (\Exception $e) {
            if ($store = $this->storeManager->getDefaultStoreView()) {
                return $store;
            }
        }

        $stores = $this->storeManager->getStores();
        return reset($stores);
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUrl(string $type): string
    {
        return (string)$this->getStore()->getBaseUrl($type);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentLogo(string $code)
    {
        if (!$this->displayPaymentImages()) {
            return false;
        }

        $logo = sprintf('%s::images/%s.svg', self::MODULE_CODE, $this->getShortMethodCode($code));
        return $this->assetRepository->getUrl($logo);
    }

    /**
     * {@inheritDoc}
     */
    public function displayPaymentImages(): bool
    {
        return (bool)$this->getFlag(self::XML_PATH_IMAGES);
    }

    public function isManualCaptureEnabled(string $methodCode, int $storeId = 0): bool
    {
        $path = 'payment/' . $methodCode . '/manual_capture';
        return (bool)$this->getStoreConfig($path, $storeId);
    }

}
