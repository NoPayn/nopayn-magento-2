<?php
declare(strict_types=1);

namespace GingerPay\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use GingerPay\Payment\Model\Methods\Creditcard;
use GingerPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * SalesOrderInvoiceSaveAfter observer class
 */
class SalesOrderInvoiceSaveAfter implements ObserverInterface
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
     * SalesOrderInvoiceSaveAfter constructor.
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
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();

        /** @var OrderInterface $order */
        $order = $invoice->getOrder();
        $method = $order->getPayment()->getMethod();
        $isManualCaptureEnabled = $this->configRepository->isManualCaptureEnabled($method, (int)$order->getStoreId());
        $transactionId = $order->getGingerpayTransactionId();

        // Log debug information
        $this->configRepository->addTolog('debug', sprintf(
            'SalesOrderInvoiceSaveAfter: Method: %s, Manual Capture Enabled: %s, Transaction ID: %s',
            $method,
            $isManualCaptureEnabled ? 'Yes' : 'No',
            $transactionId ?: 'Not available'
        ));

        // Check if this is a refund operation by looking for credit memos
        $payment = $order->getPayment();
        $isRefund = false;

        if ($payment && $payment->getCreditmemo()) {
            $isRefund = true;
            $this->configRepository->addTolog('debug', 'Skipping capture for refund operation on order: ' . $order->getIncrementId());
        }

        // Only capture credit card payments with manual capture enabled and not during refund operations
        if ($method === Creditcard::METHOD_CODE && $isManualCaptureEnabled && !$isRefund) {
            try {
                $this->configRepository->addTolog('debug', 'Attempting to capture payment for order: ' . $order->getIncrementId());
                $this->creditcardModel->captureOrder($order);
                $this->configRepository->addTolog('debug', 'Payment capture completed successfully');
            } catch (\Exception $e) {
                $this->configRepository->addTolog('error', 'Error capturing payment: ' . $e->getMessage());
            }
        }
    }
}
