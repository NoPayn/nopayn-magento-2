<?php

namespace GingerPay\Payment\Model;

use GingerPay\Payment\Model\Methods\Creditcard;
use GingerPay\Payment\Model\PaymentLibrary;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Framework\DataObject;


class AbstractPayment extends PaymentLibrary
{
    /**
     * @var string
     */
    private $paymentName;

    /**
     * @var string
     */
    private $testApiKey;

    /**
     * @param OrderInterface $order
     *
     * @return array
     * @throws \Exception
     * @throws LocalizedException
     */
    public function startTransaction(OrderInterface $order): array
    {
        return parent::prepareTransaction(
            $order,
            $this->platform_code,
            $this->method_code
        );
    }

    /**
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isAvailable(CartInterface $quote = null): bool
    {
        return parent::isAvailable($quote);
    }

    /**
     * @param string $method
     *
     * @return $this->testApiKey
     */

    private function getTestApiKey($method, $testModus, $storeId)
    {
        return $this->testApiKey;
    }

    /**
     * @param string $method
     * @param OrderInterface $order
     *
     * @return $this
     * @throws \Exception
     */
    protected function capturing(string $method, OrderInterface $order): self
    {
        $storeId = (int)$order->getStoreId();
        $this->paymentName = 'Creditcard';


        $client = $this->loadGingerClient($storeId);

        try {
            // Check if transaction ID is available
            $transactionId = $order->getGingerpayTransactionId();
            if (!$transactionId) {
                throw new \Exception('Transaction ID not available for order: ' . $order->getIncrementId());
            }

            $this->configRepository->addTolog('debug', 'Getting order from Ginger API with transaction ID: ' . $transactionId);
            $gingerOrder = $client->getOrder($transactionId);


            if (empty($gingerOrder['id'])) {
                throw new \Exception('Invalid response from Ginger API for transaction ID: ' . $transactionId);
            }


            $orderId = $gingerOrder['id'];

            if (empty($gingerOrder['transactions']) || !is_array($gingerOrder['transactions'])) {
                throw new \Exception('No transactions found in Ginger order: ' . $orderId);
            }

            $transaction = current($gingerOrder['transactions']);
            if (empty($transaction['id'])) {
                throw new \Exception('Transaction ID not found in Ginger order: ' . $orderId);
            }

            $transactionId = $transaction['id'];

            $this->configRepository->addTolog('debug', 'Capturing transaction: ' . $transactionId . ' for order: ' . $orderId);
            $client->captureOrderTransaction($orderId, $transactionId);

            $this->configRepository->addTolog(
                'success',
                $this->paymentName . ' payment captured for order: ' . $order->getIncrementId()
            );

            if ($method === Creditcard::METHOD_CODE) {
                $msg = __('Payment has been captured successfully for this order.');
                $this->messageManager->addSuccessMessage($msg);
            }

        } catch (\Exception $e) {
            $logMsg = 'Function: captureOrder: ' . $e->getMessage();

            if ($method === Creditcard::METHOD_CODE) {
                $msg = __('Warning: Unable to capture Creditcard Payment for this order, full detail: var/log/ginger-payment.log');
                $this->messageManager->addErrorMessage($msg);
            }

            $this->configRepository->addTolog('error', $logMsg);
        }

        return $this;
    }

    /**
     * @param string $method
     * @param OrderInterface $order
     * @param float|null $amount
     *
     * @return $this
     * @throws \Exception
     */
    protected function voiding(string $method, OrderInterface $order): self
    {
        $storeId = (int)$order->getStoreId();

        switch ($method) {
            case Creditcard::METHOD_CODE:
                $this->paymentName = 'Creditcard';
                break;

            default:
                $this->paymentName = 'Unknown';
        }

        $client = $this->loadGingerClient($storeId);

        try {
            $transactionId = $order->getGingerpayTransactionId();
            if (!$transactionId) {
                throw new \Exception('Transaction ID not available for order: ' . $order->getIncrementId());
            }

            $this->configRepository->addTolog('debug', 'Getting order from Ginger API with transaction ID: ' . $transactionId);
            $gingerOrder = $client->getOrder($transactionId);

            if (empty($gingerOrder['id'])) {
                throw new \Exception('Invalid response from Ginger API for transaction ID: ' . $transactionId);
            }

            $orderId = $gingerOrder['id'];

            if (empty($gingerOrder['transactions']) || !is_array($gingerOrder['transactions'])) {
                throw new \Exception('No transactions found in Ginger order: ' . $orderId);
            }

            $transaction = current($gingerOrder['transactions']);
            if (empty($transaction['id'])) {
                throw new \Exception('Transaction ID not found in Ginger order: ' . $orderId);
            }

            $transactionId = $transaction['id'];

            $this->configRepository->addTolog('debug', 'Voiding transaction: ' . $transactionId . ' for order: ' . $orderId);

            $data = [
                'amount' => $this->configRepository->getAmountInCents($order->getGrandTotal()),
                'description' => "Void for Magento shop"
            ];

            $client->send('POST', sprintf('/orders/%s/transactions/%s/voids/amount/', $orderId, $transactionId),$data);

            $this->configRepository->addTolog(
                'success',
                $this->paymentName . ' payment voided for order: ' . $order->getIncrementId()
            );

            if ($method === Creditcard::METHOD_CODE) {
                $msg = __('Payment has been voided successfully for this order.');
                $this->messageManager->addSuccessMessage($msg);
            }

        } catch (\Exception $e) {
            $logMsg = 'Function: voidOrder: ' . $e->getMessage();

            if ($method === Creditcard::METHOD_CODE) {
                $msg = __('Warning: Unable to void Creditcard Payment for this order, full detail: var/log/ginger-payment.log');
                $this->messageManager->addErrorMessage($msg);
            }

            $this->configRepository->addTolog('error', $logMsg);
        }

        return $this;
    }


    /**


    /**
     * @param string $method
     * @param InfoInterface $payment
     * @param float $amount
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function refundFunctionality($method, InfoInterface $payment, $amount)
    {
        /** @var Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();

        /** @var Order $order */
        $order = $payment->getOrder();

        if ($creditmemo->getAdjustmentPositive() != 0 || $creditmemo->getAdjustmentNegative() != 0) {
            throw new LocalizedException(__('Api does not accept adjustment fees for refunds using order lines'));
        }

        if ($creditmemo->getShippingAmount() > 0
            && ($creditmemo->getShippingAmount() != $creditmemo->getBaseShippingInclTax())) {
            throw new LocalizedException(__('Api does not accept adjustment fees for shipments using order lines'));
        }

        $storeId = (int)$order->getStoreId();
        $testModus = $order->getPayment()->getAdditionalInformation();
        if (array_key_exists('test_modus', $testModus)) {
            $testModus = $testModus['test_modus'];
        }

        $testApiKey = $this->getTestApiKey($method, $testModus, $storeId);
        $transactionId = $order->getGingerpayTransactionId();

        try {
            $addShipping = $creditmemo->getShippingAmount() > 0 ? 1 : 0;
            $client = $this->loadGingerClient($storeId, $testApiKey);

            $gingerOrder = $client->refundOrder(
                $transactionId,
                [
                    'amount' => $this->configRepository->getAmountInCents((float)$amount),
                    'currency' => $order->getOrderCurrencyCode(),
                    'order_lines' => $this->orderLines->getRefundLines($creditmemo, $addShipping)
                ]);
        } catch (\Exception $e) {
            $errorMsg = __('Error: not possible to create an online refund: %1', $e->getMessage());
            $this->configRepository->addTolog('error', $errorMsg);
            throw new LocalizedException($errorMsg);
        }

        return $this;
    }

}
