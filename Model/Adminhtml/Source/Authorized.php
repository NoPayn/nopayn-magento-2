<?php
/**
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace GingerPay\Payment\Model\Adminhtml\Source;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

/**
 * Authorized Status class
 */
class Authorized extends Status
{
    protected $_stateStatuses = [Order::STATE_PENDING_PAYMENT];
}
