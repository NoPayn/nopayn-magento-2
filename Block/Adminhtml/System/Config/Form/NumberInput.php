<?php

namespace GingerPay\Payment\Block\Adminhtml\System\Config\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

class NumberInput extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setType('number');
        $element->setData('min', 1);
        return parent::_getElementHtml($element);
    }
}
