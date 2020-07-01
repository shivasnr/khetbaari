<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Knowband\Marketplace\Block\Order\View;
class View extends \Knowband\Marketplace\Block\Order\General
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
        \Knowband\Marketplace\Helper\Log $mpLogHelper,
        \Magento\Sales\Model\Order\ItemFactory $orderItemFactory
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        $this->_timezone = $timezone;
        $this->mp_dataHelper = $mpDataHelper;
        $this->_orderItemFactory = $orderItemFactory;
        parent::__construct($context, $objectManager, $registry, $timezone, $productModel, $eventManager, $mpSellerModel, $mpShipmentModel, $mpDataHelper, $mpLogHelper);
    }

    /**
     * check if the order is applicable for creating shipment
     *
     * @return boolean
     */
    public function canCreateShipment() {
        $can_ship = false;
        if ($this->getOrder()->canShip()) {
            $seller_info = $this->mp_dataHelper->getSellerInfo();

            $orderItemCollection = $this->_orderItemFactory->create()->getCollection();
            $orderItemCollection->getSelect()->join(['order' => $orderItemCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");
            $orderItemCollection->getSelect()
                    ->join(
                            ['seller_product' => $orderItemCollection->getTable("vss_mp_product_to_seller")], "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $seller_info['entity_id'] . ")", []
            );
            $orderItemCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['main_table.*'])
                    ->where('order.entity_id = ' . $this->order->getId() . ' AND main_table.parent_item_id IS NULL');

            foreach ($orderItemCollection as $item) {
                if ($item->getQtyToShip() > 0 && !$item->getIsVirtual() && !$item->getLockedDoShip()) {
                    $can_ship = true;
                }
            }
        }

        return $can_ship;
    }

    /**
     * Get shipment tracking data of current order
     *
     * @return array()
     */
    public function getShipmentTrackings() {
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $collection = $this->_objectManager->get("Magento\Sales\Model\Order\Shipment\Track")->getCollection();

        $collection->getSelect()
                ->join(
                        ['s' => $collection->getTable("vss_seller_shipments")], "(main_table.parent_id = s.shipment_id)", []
        );
        $collection->getSelect()->where('main_table.order_id = ' . (int) $this->getOrder()->getId() . ' AND s.seller_id = ' . (int) $seller_info['entity_id']);

        return $collection->getData();
    }

    /**
     * check if the order is applicable for creating memo
     *
     * @return boolean
     */
    public function canCreateMemo() {
        if ($this->getOrder()->canCreditmemo()) {
            $seller_info = $this->mp_dataHelper->getSellerInfo();

            $orderItemCollection = $this->_orderItemFactory->create()->getCollection();
            $orderItemCollection->getSelect()->join(['order' => $orderItemCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");
            $orderItemCollection->getSelect()
                    ->join(
                            ['seller_product' => $orderItemCollection->getTable("vss_mp_product_to_seller")], "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $seller_info['entity_id'] . ")", []
            );
            $orderItemCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['main_table.*'])
                    ->where('order.entity_id = ' . $this->order->getId() . ' AND main_table.parent_item_id IS NULL');

            foreach ($orderItemCollection->getData() as $p) {
                $memo = $this->_objectManager->create("Magento\Sales\Model\Order\Creditmemo\Item")->load($p['item_id'], 'order_item_id');
                $memo_data = $memo->getData();
                $memo->unsetData();
                if (empty($memo_data) || ($p['qty_ordered'] > $p['qty_refunded'])) {
                    return true;
                }
            }
        }
        return false;
    }
    

    /**
     * check if the order can be send via email
     *
     * @return boolean
     */
    public function canSendEmail() {
        if (!$this->getOrder()->isCanceled()) {
            $seller_info = $this->mp_dataHelper->getSellerInfo();

            $orderItemCollection = $this->_orderItemFactory->create()->getCollection();
            $orderItemCollection->getSelect()->join(['order' => $orderItemCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");
            $orderItemCollection->getSelect()
                    ->join(
                            ['seller_product' => $orderItemCollection->getTable("vss_mp_product_to_seller")], "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $seller_info['entity_id'] . ")", []
            );
            $orderItemCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['main_table.*'])
                    ->where('order.entity_id = ' . $this->order->getId() . ' AND main_table.parent_item_id IS NULL');

            foreach ($orderItemCollection->getData() as $p) {
                $memo = $this->_objectManager->create("Magento\Sales\Model\Order\Creditmemo\Item")->load($p['item_id'], 'order_item_id');
                $memo_data = $memo->getData();
                $memo->unsetData();
                if (empty($memo_data) || ($p['qty_ordered'] > $p['qty_refunded'])) {
                    return true;
                    break;
                }
            }
        }
        return false;
    }

    
    /**
     * check if the invoice of order can be downloaded or not
     *
     * @return boolean
     */
    public function canDownloadInvoice() {
        $seller_info = $this->mp_dataHelper->getSellerInfo();

        $orderItemCollection = $this->_orderItemFactory->create()->getCollection();
        $orderItemCollection->getSelect()->join(['order' => $orderItemCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");
        $orderItemCollection->getSelect()
                ->join(
                        ['seller_product' => $orderItemCollection->getTable("vss_mp_product_to_seller")], "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $seller_info['entity_id'] . ")", []
        );
        $orderItemCollection->getSelect()
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns(['main_table.*'])
                ->where('order.entity_id = ' . $this->order->getId() . ' AND main_table.parent_item_id IS NULL');

        foreach ($orderItemCollection->getData() as $p) {
            $memo = $this->_objectManager->create("Magento\Sales\Model\Order\Invoice\Item")->load($p['item_id'], 'order_item_id');
            $memo_data = $memo->getData();
            $memo->unsetData();
            if (empty($memo_data) || ($p['qty_ordered'] > $p['qty_refunded'])) {
                return true;
                break;
            }
        }
        return false;
    }

    /**
     * check if the shipment of order can be downloaded or not
     *
     * @return boolean
     */
    public function canDownloadShipment() {
         $seller_info = $this->mp_dataHelper->getSellerInfo();

        $orderItemCollection = $this->_orderItemFactory->create()->getCollection();
        $orderItemCollection->getSelect()->join(['order' => $orderItemCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");
        $orderItemCollection->getSelect()
                ->join(
                        ['seller_product' => $orderItemCollection->getTable("vss_mp_product_to_seller")], "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $seller_info['entity_id'] . ")", []
        );
        $orderItemCollection->getSelect()
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns(['main_table.*'])
                ->where('order.entity_id = ' . $this->order->getId() . ' AND main_table.parent_item_id IS NULL');

        foreach ($orderItemCollection->getData() as $p) {
            $memo = $this->_objectManager->create("Magento\Sales\Model\Order\Shipment\Item")->load($p['item_id'], 'order_item_id');
            $memo_data = $memo->getData();
            $memo->unsetData();
            if (empty($memo_data) || ($p['qty_ordered'] > $p['qty_refunded'])) {
                return true;
                break;
            }
        }
        return false;
    }

}
