<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hariyo\Marketplace\Block\Order\Items;

/**
 * Abstract block for display sales (quote/order/invoice etc.) items
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class AbstractItems extends \Magento\Framework\View\Element\Template
{
    
    /**
     * Block alias fallback
     */
    const DEFAULT_TYPE = 'default';
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            array $data = []
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_productModel = $this->_objectManager->create("\Magento\Catalog\Model\Product");
        $this->mp_dataHelper = $this->_objectManager->get("\Hariyo\Marketplace\Helper\Data");
        parent::__construct($context);
    }

    /**
     * Retrieve item renderer block
     *
     * @param string $type
     * @return \Magento\Framework\View\Element\AbstractBlock
     * @throws \RuntimeException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getItemRenderer($type)
    {
        /** @var $renderer \Magento\Sales\Block\Adminhtml\Items\AbstractItems */
        $renderer = $this->getChildBlock($type) ?: $this->getChildBlock(self::DEFAULT_TYPE);
        if (!$renderer instanceof \Magento\Framework\View\Element\BlockInterface) {
            throw new \RuntimeException('Renderer for type "' . $type . '" does not exist.');
        }
        $renderer->setColumnRenders($this->getLayout()->getGroupChildNames($this->getNameInLayout(), 'column'));

        return $renderer;
    }

    /**
     * Prepare item before output
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $renderer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _prepareItem(\Magento\Framework\View\Element\AbstractBlock $renderer)
    {
        return $this;
    }

    /**
     * Return product type for quote/order item
     *
     * @param \Magento\Framework\DataObject $item
     * @return string
     */
    protected function _getItemType(\Magento\Framework\DataObject $item)
    {
        if ($item->getOrderItem()) {
            $type = $item->getOrderItem()->getProductType();
        } elseif ($item instanceof \Magento\Quote\Model\Quote\Address\Item) {
            $type = $item->getQuoteItem()->getProductType();
        } else {
            $type = $item->getProductType();
        }
        return $type;
    }

    /**
     * Get item row html
     *
     * @param   \Magento\Framework\DataObject $item
     * @return  string
     */
    public function getItemHtml(\Magento\Framework\DataObject $item)
    {
        if ($item->getOrderItem()) {
            $type = $item->getOrderItem()->getProductType();
        } else {
            $type = $item->getProductType();
        }

        if($type == 'bundle'){
            
        } else if($type == 'downloadable'){
            
        } else if($type == 'grouped'){
            
        } else {
            $blockObj= $this->getLayout()->createBlock('Hariyo\Marketplace\Block\Order\Item\Renderer\DefaultRenderer')->setItem($item)->setTemplate('order/view/items/default.phtml');
        }
//        return $this->getItemRenderer($type)->setItem($item)->setCanEditQty($this->canEditQty())->toHtml();
        return $blockObj->toHtml();
    }
    
    /**
     * Check if the product is of any seller or not
     *
     * @param   string $sku
     * @return  boolean
     */
    public function isSellerProduct($sku = '') {
        $is_seller_product = false;
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $product = $this->_productModel->getCollection();
        $product->getSelect()->join(['s2p' => $product->getTable('vss_mp_product_to_seller')], 'e.entity_id = s2p.product_id');
        $product->getSelect()->where('e.sku="' . $sku . '" and s2p.seller_id=' . $seller_info['entity_id']);
        if ($product->getSize() > 0) {
            $is_seller_product = true;
        }
        unset($product);
        return $is_seller_product;
    }
}
