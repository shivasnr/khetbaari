<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Knowband\Marketplace\Block\Order;
class Comments extends \Knowband\Marketplace\Block\Order\General
{
   protected $block_parent = null;
   
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Framework\Event\Manager $eventManager,
        \Knowband\Marketplace\Model\Seller $mpSellerModel,
        \Knowband\Marketplace\Model\Shipments $mpShipmentModel,
        \Knowband\Marketplace\Helper\Data $mpDataHelper,
        \Knowband\Marketplace\Helper\Log $mpLogHelper,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Sales\Helper\Data $salesDataHelper
    ) {
        $this->adminHelper = $adminHelper;
        $this->salesDataHelper = $salesDataHelper;
        parent::__construct($context, $objectManager, $registry, $timezone, $productModel, $eventManager, $mpSellerModel, $mpShipmentModel, $mpDataHelper, $mpLogHelper);
        if ($this->getRequest()->getControllerName() == 'shipment') {
            $this->block_parent = 'shipment';
        } elseif ($this->getRequest()->getControllerName() == 'creditmemo') {
            $this->block_parent = 'creditmemo';
        }
    }

    public function getEntity() {
        switch ($this->block_parent) {
            case 'shipment':
                return $this->getShipment();
            case 'creditmemo':
                return $this->getCreditmemo();
        }

        return $this->getOrder();
    }

    public function escapeHtml($data, $allowedTags = null) {
        return $this->adminHelper->escapeHtmlWithLinks($data, $allowedTags);
    }

    public function canSendCommentEmail() {
        switch ($this->block_parent) {
            case 'shipment':
                return $this->salesDataHelper->canSendShipmentCommentEmail(
                                $this->getShipment()->getOrder()->getStore()->getId()
                );
            case 'creditmemo':
                return $this->salesDataHelper->canSendCreditmemoCommentEmail(
                                $this->getEntity()->getOrder()->getStore()->getId()
                );
        }

        return true;
    }

    public function historyTitle() {
        $title = __('Comment History');
        switch ($this->block_parent) {
            case 'shipment':
                $title = __('Shipment History');
                break;
            case 'creditmemo':
                $title = __('Credit Memo History');
                break;
        }
        return $title;
    }

    public function getSubmitUrl() {
        switch ($this->block_parent) {
            case 'shipment':
                return $this->getUrl('*/shipment/addComment', ['id' => $this->getEntity()->getId()]);
            case 'creditmemo':
                return $this->getUrl('*/creditmemo/addComment', ['id' => $this->getEntity()->getId()]);
        }
        return '';
    }

}
