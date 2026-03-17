<?php
declare(strict_types=1);

namespace GingerPay\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use GingerPay\Payment\Model\Methods\Creditcard;
use GingerPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * SalesOrderCancelAfter observer class
 */
class SalesOrderCancelAfter implements ObserverInterface
{
    /**
     * @var Creditcard
     */
    private $creditcardModel;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * SalesOrderCancelAfter constructor.
     *
     * @param Creditcard $creditcardModel
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        Creditcard $creditcardModel,
        ConfigRepository $configRepository
    ) {
        $this->creditcardModel = $creditcardModel;
        $this->configRepository = $configRepository;
    }

    /**
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();
        $method = $order->getPayment()->getMethod();
        $isManualCaptureEnabled = $this->configRepository->isManualCaptureEnabled($method, (int)$order->getStoreId());
        $transactionId = $order->getGingerpayTransactionId();

        // Log debug information
        $this->configRepository->addTolog('debug', sprintf(
            'SalesOrderCancelAfter: Method: %s, Manual Capture Enabled: %s, Transaction ID: %s',
            $method,
            $isManualCaptureEnabled ? 'Yes' : 'No',
            $transactionId ?: 'Not available'
        ));

        // Only void supported manual-capture payment methods
        if ($isManualCaptureEnabled) {
            try {
                $isVoided = false;
                $this->configRepository->addTolog('debug', 'Attempting to void payment for order: ' . $order->getIncrementId());
                if ($method === Creditcard::METHOD_CODE) {
                    $this->creditcardModel->voidOrder($order);
                    $isVoided = true;
                } else {
                    $this->configRepository->addTolog('debug', 'Skipping void for unsupported method: ' . $method);
                }
                if ($isVoided) {
                    $this->configRepository->addTolog('debug', 'Payment void completed successfully');
                }
            } catch (\Exception $e) {
                $this->configRepository->addTolog('error', 'Error voiding payment: ' . $e->getMessage());
            }
        }
    }
}
