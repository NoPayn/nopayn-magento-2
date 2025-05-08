<?php
/**
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace GingerPay\Payment\ViewModel\Checkout;

use GingerPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use GingerPay\Payment\Model\PaymentLibrary as PaymentLibraryModel;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Success view model class
 */
class Success implements ArgumentInterface
{


    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var PaymentLibraryModel
     */
    private $paymentLibraryModel;

    /**
     * Success constructor.
     *
     * @param Session $checkoutSession
     * @param ConfigRepository $configRepository
     * @param PaymentLibraryModel $paymentLibraryModel
     */
    public function __construct(
        Session $checkoutSession,
        ConfigRepository $configRepository,
        PaymentLibraryModel $paymentLibraryModel
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->configRepository = $configRepository;
        $this->paymentLibraryModel = $paymentLibraryModel;
    }

    /**
     * @return string
     */
    public function getMailingAddress(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getThankYouMessage(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        $storeId = $this->configRepository->getCurrentStoreId();
        return $this->configRepository->getCompanyName((int)$storeId);
    }
}
