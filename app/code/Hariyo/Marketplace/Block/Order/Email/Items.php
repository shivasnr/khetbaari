<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hariyo\Marketplace\Block\Order\Email;
class Items extends \Hariyo\Marketplace\Block\Order\General
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
        \Hariyo\Marketplace\Model\Seller $mpSellerModel,
        \Hariyo\Marketplace\Model\Shipments $mpShipmentModel,
        \Hariyo\Marketplace\Helper\Data $mpDataHelper,
        \Psr\Log\LoggerInterface $mpLogHelper
    ) {
        $this->_registry = $registry;
        parent::__construct($context, $objectManager, $registry, $timezone, $productModel, $eventManager, $mpSellerModel, $mpShipmentModel, $mpDataHelper, $mpLogHelper);
    }

    protected function _beforeToHtml()
    {
        $this->_initTotals();
    }
    
    public function isSellerProduct($sku = '') {
        $current_seller_id = (int) $this->_registry->registry("order_email_current_seller_id");
        $is_seller_product = false;
        $product = $this->_productModel->getCollection();
        $product->getSelect()->join(['s2p' => $product->getTable('vss_mp_product_to_seller')], 'e.entity_id = s2p.product_id');
        $product->getSelect()->where('e.sku="' . $sku . '" and s2p.seller_id=' . $current_seller_id);
        if ($product->getSize() > 0) {
            $is_seller_product = true;
        }
        unset($product);
        return $is_seller_product;
    }
}
