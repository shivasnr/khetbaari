<?php

namespace Knowband\Marketplace\Block\Adminhtml\Renderers\Actions;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;

class OrderTotal extends AbstractRenderer {

    /**
     * @var UrlInterface
     */
    protected $_frameworkUrlInterface;

    public function render(DataObject $item) {
        $calculated_price = $item->getRowTotalInclTax() - $item->getDiscountAmount();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $priceHelper = $objectManager->get("\Magento\Framework\Pricing\Helper\Data");
        return $priceHelper->currency($calculated_price, true, false);
    }

}