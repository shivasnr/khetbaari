<?php

namespace Knowband\Marketplace\Block\Product;

class MassUpdate extends \Knowband\Marketplace\Block\Product\Base {
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        parent::__construct($context, $setsFactory, $objectManager, $registry);
    }
    
    public function getFieldId() {
        return $this->getParentBlock()->getFieldId();
    }

    public function getMassDeleteUrl() {
        return $this->_objectManager->get("\Knowband\Marketplace\Helper\Data")->getFrontUrl('product', 'delete', ['action' => 'mass']);
    }

    public function getMassStatusUrl() {
        return $this->_objectManager->get("\Knowband\Marketplace\Helper\Data")->getFrontUrl('product', 'statusUpdate', ['action' => 'mass']);
    }

    public function showStatusOption() {
        $sellerHelper = $this->_objectManager->get("\Knowband\Marketplace\Helper\Seller");
        return ($sellerHelper->isApprovedSeller() && $sellerHelper->isEnabledSeller());
    }

}
