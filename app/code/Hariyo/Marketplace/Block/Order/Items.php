<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hariyo\Marketplace\Block\Order;
class Items extends \Hariyo\Marketplace\Block\Order\Items\AbstractItems
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
        \Magento\Framework\Registry $registry,
        array $data = [],
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollectionFactory = null
    ) {
        $this->_coreRegistry = $registry;
        $this->itemCollectionFactory = $itemCollectionFactory ?: \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory::class);
        parent::__construct($context, $data);
    }

    /**
     * Init pager block and item collection with page size and current page number.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->itemsPerPage = $this->_scopeConfig->getValue('sales/orders/items_per_page');

        $this->itemCollection = $this->itemCollectionFactory->create();
        $this->itemCollection->setOrderFilter($this->getOrder());
        $this->itemCollection->filterByParent(null);

        /** @var \Magento\Theme\Block\Html\Pager $pagerBlock */
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');
        if ($pagerBlock) {
            $pagerBlock->setLimit($this->itemsPerPage);
            //here pager updates collection parameters
            $pagerBlock->setCollection($this->itemCollection);
            $pagerBlock->setAvailableLimit([$this->itemsPerPage]);
            $pagerBlock->setShowAmounts($this->isPagerDisplayed());
        }

        return parent::_prepareLayout();
    }

    /**
     * Determine if the pager should be displayed for order items list.
     * To be called from templates(after _prepareLayout()).
     *
     * @return bool
     */
    public function isPagerDisplayed()
    {
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');

        return $pagerBlock && ($this->itemCollection->getSize() > $this->itemsPerPage);
    }

    /**
     * Get visible items for current page.
     * To be called from templates(after _prepareLayout()).
     *
     * @return \Magento\Framework\DataObject[]
     */
    public function getItems()
    {
        return $this->itemCollection->getItems();
    }

    /**
     * Get pager HTML according to our requirements.
     * To be called from templates(after _prepareLayout()).
     *
     * @return string HTML output
     */
    public function getPagerHtml()
    {
        /** @var \Magento\Theme\Block\Html\Pager $pagerBlock */
        $pagerBlock = $this->getChildBlock('sales_order_item_pager');
        return $pagerBlock ? $pagerBlock->toHtml() : '';
    }

    /**
     * Retrieve current order model instance.
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }
}