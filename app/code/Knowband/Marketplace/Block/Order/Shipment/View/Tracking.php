<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Knowband\Marketplace\Block\Order\Shipment\View;
class Tracking extends \Knowband\Marketplace\Block\Order\General
{
   /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Order items per page.
     *
     * @var int
     */
    private $itemsPerPage;

    /**
     * Sales order item collection factory.
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    private $itemCollectionFactory;

    /**
     * Sales order item collection.
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection|null
     */
    private $itemCollection;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory|null $itemCollectionFactory
     */
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
        \Knowband\Marketplace\Helper\Log $mpLogHelper
    ) {
        parent::__construct($context, $objectManager, $registry, $timezone, $productModel, $eventManager, $mpSellerModel, $mpShipmentModel, $mpDataHelper, $mpLogHelper);
    }
    
    public function getCarrierTitle($code)
    {
        if ($carrier = $this->_objectManager->get('\Magento\Shipping\Model\Config')->getCarrierInstance($code)) {
            return $carrier->getConfigData('title');
        }
        else {
            return __('Custom Value');
        }
        return false;
    }
    
    public function getRemoveUrl($track)
    {
        return $this->getUrl('*/*/removeTrack/', [
            'shipment_id' => $this->getShipment()->getId(),
            'track_id' => $track->getId()
        ]);
    }

}
