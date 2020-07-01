<?php

namespace Knowband\Marketplace\Block\Seller;

class Products extends \Magento\Catalog\Block\Product\AbstractProduct {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Catalog\Block\Product\Context $productContext,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Catalog\Model\CategoryFactory $categoryFactory,
            \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
            \Magento\CatalogInventory\Helper\Stock $stockHelper,
            \Knowband\Marketplace\Model\Seller $mpSellerModel,
            \Knowband\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->imageBuilder = $productContext->getImageBuilder();
        $this->_objectManager = $objectManager;
        $this->mp_sellerModel = $mpSellerModel;
        $this->_storeManager = $context->getStoreManager();
        $this->_categoryFactory = $categoryFactory;
        $this->_productFactory = $productCollection;
        $this->mp_dataHelper = $mpDataHelper;
        $this->scopeConfig = $context->getScopeConfig();
        $this->stockHelper = $stockHelper;
        $this->_reviewFactory = $this->_objectManager->create("\Magento\Review\Model\ReviewFactory");
        parent::__construct($productContext,  []);
        
        $sellerId = $this->getRequest()->getParam('id');
        $website_id = $this->_storeManager->getWebsite()->getId();
        $cat_id = $this->getRequest()->getParam('cat_id');
        $order_by = $this->getRequest()->getParam('order');
        if ($this->getRequest()->getParam('cat_id')) {
            if ($cat_id == 'all') {
                $collection = $this->_productFactory->create();
            } else {
                $collection = $this->_categoryFactory->create()->load($this->getRequest()->getParam('cat_id'))
                        ->getProductCollection();
            }
        } else {
            $collection = $this->_productFactory->create()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSort('name', 'ASC');   
        }
        
        if ($order_by == 1) {
            $collection->addAttributeToSort('name', 'ASC');
        } else if ($order_by == 2) {
            $collection->addAttributeToSort('sku', 'ASC');
        } else if ($order_by == 3) {
            $collection->addAttributeToSort('price', 'ASC');
        } else if ($order_by == 4) {
            $collection->addAttributeToSort('price', 'DESC');
        }
        $collection->addAttributeToFilter('status', ['eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED]);
        $collection->addFieldToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);

        $this->stockHelper->addInStockFilterToCollection($collection);

        $collection->joinField('s2p', $collection->getTable('vss_mp_product_to_seller'), '', 'product_id=entity_id', [
            'seller_id' => (int) $sellerId,
            'website_id' => (int) $website_id,
            'approved' => \Knowband\Marketplace\Helper\GridAction::APPROVED
            ], 'inner');
        
        
        $this->setCollection($collection);
        $this->setTemplate('seller_products.phtml');
    }
    
    protected function _prepareLayout() {
        parent::_prepareLayout();

        $pager = $this->getLayout()->createBlock('\Magento\Theme\Block\Html\Pager', 'seller.pager');
        $pager->setAvailableLimit(
                [
                    12 => 12,
                    24 => 24,
                    36 => 36,
                    'all' => __('All')
                ]
        );
        $pager->setCollection($this->getCollection());
        $this->setChild('pager', $pager);
        $this->getCollection()->load();
        return $this;
    }

    public function getPagerHtml() {
        return $this->getChildHtml('pager');
    }
    
    public function getProductImageUrl($product){
        $mediaGallery = $product->getMediaGalleryImages();
        $image = $mediaGallery->getItemById('small_image');
        if(!$image){
            $image = $mediaGallery->getFirstItem();
        }
        return $image->getUrl();
    }
    
    /**
     * Return HTML block with price
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getProductPrice(\Magento\Catalog\Model\Product $_product)
    {
        $priceRender = $this->getLayout()->getBlock('product.price.render.default');
        if (!$priceRender) {
            $priceRender = $this->getLayout()->createBlock(
                \Magento\Framework\Pricing\Render::class,
                'product.price.render.default',
                ['data' => ['price_render_handle' => 'catalog_product_prices']]
            );
        }

        $price = '';
        if ($priceRender) {
            $price = $priceRender->render(
                \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
                $_product,
                [
                    'display_minimal_price'  => true,
                    'use_link_for_as_low_as' => true,
                    'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST
                ]
            );
        }
        return $price;
    }
    
    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($_product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->setProduct($_product)
            ->setImageId('category_page_list')
            ->setAttributes($attributes)
            ->create();
    }
    
    /**
     * Get product reviews summary
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $templateType
     * @param bool $displayIfNoReviews
     * @return string
     */
    public function getReviewsSummaryHtml(
        \Magento\Catalog\Model\Product $_product,
        $templateType = false,
        $displayIfNoReviews = false
    ) {
        if (!$_product->getRatingSummary()) {
            $this->_reviewFactory->create()->getEntitySummary($_product, $this->_storeManager->getStore()->getId());
        }
        return parent::getReviewsSummaryHtml($_product, $templateType, $displayIfNoReviews);
    }    
}
