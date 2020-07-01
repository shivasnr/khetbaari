<?php

namespace Hariyo\Marketplace\Controller\Product;

use Hariyo\Marketplace\Controller\Index\ParentController;
use Magento\Framework\Controller\ResultFactory;
use Magento\Downloadable\Api\Data\SampleInterfaceFactory as SampleFactory;
use Magento\Downloadable\Api\Data\LinkInterfaceFactory as LinkFactory;
class NewAction extends ParentController {

    protected $mp_resultRawFactory;
    protected $mp_request;
    protected $mp_scopeConfig;
    protected $inlineTranslation;
    protected $mp_transportBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var SampleFactory
     */
    private $sampleFactory;

    /**
     * @var LinkFactory
     */
    private $linkFactory;

    /**
     * @var \Magento\Downloadable\Model\Sample\Builder
     */
    private $sampleBuilder;

    /**
     * @var \Magento\Downloadable\Model\Link\Builder
     */
    private $linkBuilder;

    public function __construct(
            \Magento\Framework\App\Action\Context $context, 
            \Magento\Framework\App\Request\Http $request, 
            \Magento\Framework\App\Response\Http $response,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\Registry $registry,
            \Magento\Customer\Model\Session $customerSessionModel,
            \Magento\Framework\View\Result\PageFactory $resultRawFactory,
            \Hariyo\Marketplace\Helper\Setting $settingHelper,
            \Hariyo\Marketplace\Helper\Seller $sellerHelper,
            \Hariyo\Marketplace\Model\Seller $sellerModel,
            \Hariyo\Marketplace\Model\Product $productToSellerModel,
            \Magento\Framework\Event\Manager $eventManager
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_sellerHelper = $sellerHelper;
        $this->mp_sellerModel = $sellerModel;
        $this->mp_productToSellerModel = $productToSellerModel;
        $this->_eventManager = $eventManager;
        $this->_registry = $registry;
        $this->_objectManager = $context->getObjectManager();
    }

    public function execute() {
        $this->isLoggedIn();
        $seller_approved = $this->mp_sellerModel->load($this->_customerInfo['entity_id'], 'seller_id');
        $seller_product_limit = $seller_approved->getProductLimit();
        $seller_approved->unsetData();
        if (!$this->mp_sellerHelper->isApprovedSeller() && ($seller_product_limit >= $this->mp_settingHelper->getSettingByKey($this->_customerInfo['entity_id'], 'product_limit'))) {
            $this->messageManager->addError(__('Adding of new product limit has been over as your account is not approved or waiting for approval. To add more products, your account needs to be approved.'));
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('marketplace/product/productList');
            return $resultRedirect;
        }
        if (!$this->getRequest()->isPost()) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('marketplace/product/productList');
            return $resultRedirect;
        }

        $process_step = 3;
        if ($this->getRequest()->getParam('process_step')) {
            $process_step = $this->getRequest()->getParam('process_step');
        }

        $mode = false;
        if ($process_step == 4) {
            $mode = $this->save();
            if ($mode && !$this->_edit_mode) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setPath('marketplace/product/productList');
                return $resultRedirect;
            } elseif ($mode && $this->_edit_mode) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setPath('marketplace/product/edit', ['id' => $this->_product_id]);
                return $resultRedirect;
            }
        }

        $product = $this->_initProduct();
        $this->_eventManager->dispatch('catalog_product_new_action', ['product' => $product]);
        if ($this->getRequest()->getParam('popup')) {
            if ($this->getRequest()->getParam('process_step') == 4) {
                $this->_registry->unregister('closewindow');
                $this->_registry->register('closewindow', 1);
            }
            $resultPage = $this->mp_resultRawFactory->create();
            $resultPage->addHandle('vssmp_product_popup');
            $this->_posted_form_data['process_step'] = 4;
            $this->_posted_form_data['popup'] = true;
        } else {
            $resultPage = $this->mp_resultRawFactory->create();
            $resultPage->addHandle('marketplace_product_section');
        }
        $resultPage = $this->mp_resultRawFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__("Seller's Products") . ' - ' . __('New Product'));
        $this->_registry->register('vssmp_posted_form_data', $this->_posted_form_data);
        return $resultPage;
    }

    //Save product action
    private function save() {
        $redirectBack = true;
        $postedData = $this->getRequest()->getPost();
        
        $data = $this->_objectManager->get("\Hariyo\Marketplace\Helper\Product")->processDataBeforeSave($postedData);
        if ($data) {
            //check if the tier price is of the valid type which should be array type
            if(isset($data['tier_price']) && !is_array($data['tier_price'])){
                $data['tier_price'] = [];
            }
            $this->getRequest()->setPostValue('product', $data);

            $this->_filterStockData($data['stock_data']);

            if(isset($data['stock_data']['qty']) && isset($data['stock_data']['is_in_stock'])){
                $data['quantity_and_stock_status']['qty'] = $data['stock_data']['qty'];
                $data['quantity_and_stock_status']['is_in_stock'] = (bool) $data['stock_data']['is_in_stock'];
            }
            
            $product = $this->_initProduct();
            $wasLockedMedia = false;
            if ($product->isLockedAttribute('media')) {
                $product->unlockAttribute('media');
                $wasLockedMedia = true;
            }

            $product->addData($data);

            if ($wasLockedMedia) {
                $product->lockAttribute('media');
            }

            // Create Permanent Redirect for old URL key
            if ($product->getId() && isset($data['url_key_create_redirect'])) {
                $product->setData('save_rewrites_history', (bool) $data['url_key_create_redirect']);
            }

            //Meta Information
            $product->setWebsiteIds($data['website_ids']);
            $product->setNewFromDate($data['news_from_date']);
            $product->setNewFromDate($data['news_to_date']);
            $product->setMetaTitle($data['meta_title']);
            $product->setMetaKeyword($data['meta_keyword']);
            $product->setMetaDescription($data['meta_description']);

            //Init product links data (related)
            if (isset($data['links']['related'])) {
                $product->setRelatedLinkData($data['links']['related']);
            }

            //set grouped data
            if ($product->getTypeId() === \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE && !$product->getGroupedReadonly()) {
                $newLinks = [];
                $existingLinks = $product->getProductLinks();
                if (isset($data['links']['grouped'])) {
                    $newLinks = $data['links']['grouped'];
                }
                $existingLinks = $this->removeUnExistingLinks($existingLinks, $newLinks);
                $product->setProductLinks(array_merge($existingLinks, $newLinks));
            }

            //Initialize product categories
            $categoryIds = '';
            if (isset($postedData['selectItemcategory_ids']))
                $categoryIds = $postedData['selectItemcategory_ids'];

            // Unsetting product from each category
            $product->setCategoryIds([]);

            if (null !== $categoryIds && is_array($categoryIds) && !empty($categoryIds)) {
                $checked_allowd_categories = $categoryIds;
                $allowed_categories_str = $this->mp_settingHelper->getSettingByKey($this->_customerInfo['entity_id'], 'category_ids');
                if (isset($allowed_categories_str['seller']) && !empty($allowed_categories_str['seller'])) {
                    $allowed_categories = explode(',', $allowed_categories_str['seller']);
                    if (is_array($allowed_categories) && !empty($allowed_categories)) {
                        $checked_allowd_categories = [];
                        foreach ($categoryIds as $cat_id) {
                            if (in_array($cat_id, $allowed_categories)) {
                                $checked_allowd_categories[] = $cat_id;
                            }
                        }
                    }
                }
                $product->setCategoryIds($checked_allowd_categories);
            }

            //Initialize data for configurable product
            if (($configurable_products_data = $this->getRequest()->getPost('configurable_products_data')) && !$product->getConfigurableReadonly()
            ) {
                $product->setConfigurableProductsData($configurable_products_data);
            }
            if (($configurable_attributes_data = $this->getRequest()->getPost('configurable_attributes_data')) && !$product->getConfigurableReadonly()
            ) {
                $product->setConfigurableAttributesData($configurable_attributes_data);
            }

            $product->setCanSaveConfigurableAttributes(
                    (bool) $this->getRequest()->getPost('affect_configurable_product_attributes') && !$product->getConfigurableReadonly()
            );

            //Initialize product options
            if (isset($data['options']) && !$product->getOptionsReadonly()) {
                $product->setProductOptions($productData['options']);
            }

            $product->setCanSaveCustomOptions(true);
            if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $product->setCanSaveBundleSelections(true);
                $product->setAffectBundleProductSelections(true);
            }

            $this->_registry->register('product', $product);

            if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $bundle_data = [];
                if (isset($postedData['bundle_options']) && !empty($postedData['bundle_options'])) {
                    $bundle_options = $postedData['bundle_options'];
                    foreach ($bundle_options as $pos => $option) {
                        $bundle_data['bundle_options']['bundle_options'][$pos] = $option;
                    }
                } else {
                    $bundle_options = [];
                }

                if (isset($bundle_data['bundle_options']['bundle_options']) && !empty($bundle_data['bundle_options']['bundle_options'])) {
                    foreach ($bundle_data['bundle_options']['bundle_options'] as $key => $option) {
                        if (isset($postedData['bundle_selections'][$key])) {
                            $bundle_data['bundle_options']['bundle_options'][$key]['bundle_selections'] = $postedData['bundle_selections'][$key];
                        }
                    }
                }

                $compositeReadonly = $product->getCompositeReadonly();
                $result['bundle_selections'] = $result['bundle_options'] = [];
                if (isset($bundle_data['bundle_options']['bundle_options'])) {
                    foreach ($bundle_data['bundle_options']['bundle_options'] as $key => $option) {
                        if (empty($option['bundle_selections'])) {
                            continue;
                        }
                        $result['bundle_selections'][$key] = $option['bundle_selections'];
                        unset($option['bundle_selections']);
                        $result['bundle_options'][$key] = $option;
                    }
                    if ($result['bundle_selections'] && !$compositeReadonly) {
                        $product->setBundleSelectionsData($result['bundle_selections']);
                    }

                    if ($result['bundle_options'] && !$compositeReadonly) {
                        $product->setBundleOptionsData($result['bundle_options']);
                    }
                    $this->_objectManager->get("\Hariyo\Marketplace\Helper\Product")->processBundleOptionsData($product);
                    $this->_objectManager->get("\Hariyo\Marketplace\Helper\Product")->processDynamicOptionsData($product);
                }

                $affectProductSelections = (bool) isset($postedData['affect_bundle_product_selections'])?$postedData['affect_bundle_product_selections']:0;
                $product->setCanSaveBundleSelections($affectProductSelections && !$compositeReadonly);
                
            }

            if ($product->getTypeId() == 'downloadable') {
                if ($this->getRequest()->getParam('downloadable') && !empty($this->getRequest()->getParam('downloadable'))) {
//                    $downloadable = $this->getRequest()->getParam('downloadable');
//                    $product->setDownloadableData($downloadable);
//                    $extension = $product->getExtensionAttributes();
//                    if (isset($downloadable['link']) && is_array($downloadable['link'])) {
//                        $links = [];
//                        foreach ($downloadable['link'] as $linkData) {
//                            if (!$linkData || (isset($linkData['is_delete']) && $linkData['is_delete'])) {
//                                continue;
//                            } else {
//                                $links[] = $this->getLinkBuilder()->setData(
//                                                $linkData
//                                        )->build(
//                                        $this->getLinkFactory()->create()
//                                );
//                            }
//                        }
//                        $extension->setDownloadableProductLinks($links);
//                    }
//                    if (isset($downloadable['sample']) && is_array($downloadable['sample'])) {
//                        $samples = [];
//                        foreach ($downloadable['sample'] as $sampleData) {
//                            if (!$sampleData || (isset($sampleData['is_delete']) && (bool) $sampleData['is_delete'])) {
//                                continue;
//                            } else {
//                                $samples[] = $this->getSampleBuilder()->setData(
//                                                $sampleData
//                                        )->build(
//                                        $this->getSampleFactory()->create()
//                                );
//                            }
//                        }
//                        $extension->setDownloadableProductSamples($samples);
//                    }
//                    $product->setExtensionAttributes($extension);
//                    if ($product->getLinksPurchasedSeparately()) {
//                        $product->setTypeHasRequiredOptions(true)->setRequiredOptions(true);
//                    } else {
//                        $product->setTypeHasRequiredOptions(false)->setRequiredOptions(false);
//                    }
                }
            }
            $this->getRequest()->setPostValue('product', $data);

            try {
                $add_mapping = true;
                if ($product->getId()) {
                    $add_mapping = false;
                }

                $this->_eventManager->dispatch(
                        'seller_product_save_before', ['product' => &$product, 'add_mapping' => &$add_mapping, 'product_id' => (int) $product->getId(), 'seller_id' => $this->_customerInfo['entity_id']]
                );

                $product->save();

                $this->_eventManager->dispatch(
                        'seller_product_save_after', ['product' => $product, 'add_mapping' => &$add_mapping, 'product_id' => (int) $product->getId(), 'seller_id' => $this->_customerInfo['entity_id']]
                );

                $this->_product_id = $productId = $product->getId();
                $product->unsetData();

                $this->_registry->unregister('product');

                //Save Mapping with seller if product is new
//                if ($add_mapping) {
                $this->saveProductMappingAction($productId);
//                }

                $redirectBack = false;
                if ($this->getRequest()->getParam('popup')) {
                    $this->_registry->registry('message', ['success' => __('The product has been saved.') . ' ' . sprintf(__('This window will be closed automatically within %s secs.'), 5)]);
                } else {
                    if (!$add_mapping) {
                        $this->messageManager->addSuccess(__('Product has been saved successfully.'));
                    }
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if ($add_mapping) {
                    $message = 'Failed to save new product. Error - ' . $e->getMessage();
                } else {
                    $message = 'Failed to save after edit product(#' . $product->getId() . '). Error - ' . $e->getMessage();
                }
                if ($this->getRequest()->getParam('popup')) {
                    $this->_registry->registry('message', ['error' => $e->getMessage() . ' ' . sprintf(__('This window will be closed automatically within %s secs.'), 5)]);
                }
                $this->_logger->info($message);

                $this->messageManager->addError($e->getMessage());
                $redirectBack = true;
            } catch (\Exception $e) {
                if ($add_mapping) {
                    $message = 'Failed to save new product. Error - ' . $e->getMessage();
                } else {
                    $message = 'Failed to save after edit product(#' . $product->getId() . '). Error - ' . $e->getMessage();
                }
                $this->_logger->info($message);
                if ($this->getRequest()->getParam('popup')) {
                    $this->_registry->registry('message', ['error' => $e->getMessage() . ' ' . sprintf(__('This window will be closed automatically within %s secs.'), 5)]);
                }
                $this->messageManager->addError($e->getMessage());
                $redirectBack = true;
            }
            $product->unsetData();
        }
        if ($this->getRequest()->getParam('popup')) {
            return false;
        } elseif ($redirectBack) {
            return false;
        } else {
            return true;
        }
    }

    //Filter product stock data
    protected function _filterStockData(&$stockData) {
        if (is_null($stockData)) {
            return;
        }
        if (!isset($stockData['use_config_manage_stock'])) {
            $stockData['use_config_manage_stock'] = 0;
        }
        if (isset($stockData['qty']) && (float) $stockData['qty'] > \Magento\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter::MAX_QTY_VALUE) {
            $stockData['qty'] = \Magento\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter::MAX_QTY_VALUE;
        }
        if (isset($stockData['min_qty']) && (int) $stockData['min_qty'] < 0) {
            $stockData['min_qty'] = 0;
        }
        if (!isset($stockData['is_decimal_divided']) || $stockData['is_qty_decimal'] == 0) {
            $stockData['is_decimal_divided'] = 0;
        }
    }

    private function saveProductMappingAction($product_id) {
        $need_approval = \Hariyo\Marketplace\Helper\GridAction::APPROVED;
        if ($this->mp_settingHelper->getSettingByKey($this->_customerInfo['entity_id'], 'product_approval')) {
            $need_approval = \Hariyo\Marketplace\Helper\GridAction::WAITING_APPROVAL;
        }

        try {
            $product_to_seller_coll = $this->mp_productToSellerModel->getCollection()
                    ->addFieldToFilter("seller_id", ["eq" => $this->_customerInfo['entity_id']])
                    ->addFieldToFilter("product_id", ["eq" => $product_id]);
            if (!$product_to_seller_coll->getSize()) {
                $product_to_seller = $this->mp_productToSellerModel
                        ->setWebsiteId($this->mp_storeManager->getStore()->getWebsiteId());
                $product_to_seller->addData(['seller_id' => $this->_customerInfo['entity_id'], 'product_id' => $product_id, 'approved' => $need_approval]);
                $product_to_seller->save();
                $product_to_seller->unsetData();

                $this->_eventManager->dispatch(
                        'seller_product_mapping_save_after', ['product_id' => $product_id, 'seller_id' => $this->_customerInfo['entity_id'], 'need_approval' => &$need_approval]
                );

                if ($need_approval == \Hariyo\Marketplace\Helper\GridAction::WAITING_APPROVAL) {
                    $this->_objectManager->get("\Hariyo\Marketplace\Helper\Product")->statusUpdateOnProductApproval($product_id, $need_approval, $this->_customerInfo['entity_id']);
//                    $this->_objectManager->get("\Hariyo\Marketplace\Helper\Email")->sendProductApprovalRequestEmail($this->_customerInfo['entity_id'], $product_id);
                }

                $msg = __('Product has been saved successfully.');
                if ($this->getRequest()->getActionName() == 'duplicate') {
                    $msg = __('The product has been duplicated successfully.');
                } else if ($this->getRequest()->getActionName() == 'quickCreate') {
                    $msg = __('Product has been created successfully.');
                }

                if ($need_approval == \Hariyo\Marketplace\Helper\GridAction::WAITING_APPROVAL) {
                    $msg .= ' ' . __('Please wait for admin approval.');
                }

                $this->messageManager->addSuccess($msg);

                try {
                    $seller_coll = $this->mp_sellerModel->load($this->_customerInfo['entity_id'], 'seller_id');
                    $seller_coll->setProductLimit((int) $seller_coll->getProductLimit() + 1);
                    $seller_coll->save();
                    $seller_coll->unsetData();
                } catch (\Exception $e) {
                    $message = 'Failed to increment product limit for product(#' . $product_id . ') to seller(#' . $this->_customerInfo['entity_id'] . '). Error - ' . $e->getMessage();
                    $this->_logger->info($message);
                }
            }
        } catch (\Exception $e) {
            $message = 'Failed to save new product(#' . $product_id . ') mapping with seller(#' . $this->_customerInfo['entity_id'] . '). Error - ' . $e->getMessage();
            $this->_logger->info($message);
        }
    }

    private function removeUnExistingLinks($existingLinks, $newLinks)
    {
        $result = [];
        foreach ($existingLinks as $key => $link) {
            $result[$key] = $link;
            if ($link->getLinkType() == \Magento\GroupedProduct\Model\Product\Initialization\Helper\ProductLinks\Plugin\Grouped::TYPE_NAME) {
                $exists = false;
                foreach ($newLinks as $newLink) {
                    if ($link->getLinkedProductSku() == $newLink->getLinkedProductSku()) {
                        $exists = true;
                    }
                }
                if (!$exists) {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }

    /**
     * Get LinkBuilder instance
     *
     * @deprecated
     * @return \Magento\Downloadable\Model\Link\Builder
     */
    private function getLinkBuilder()
    {
        if (!$this->linkBuilder) {
            $this->linkBuilder = $this->_objectManager->get(\Magento\Downloadable\Model\Link\Builder::class);
}

        return $this->linkBuilder;
    }

    /**
     * Get SampleBuilder instance
     *
     * @deprecated
     * @return \Magento\Downloadable\Model\Sample\Builder
     */
    private function getSampleBuilder()
    {
        if (!$this->sampleBuilder) {
            $this->sampleBuilder = $this->_objectManager->get(
                \Magento\Downloadable\Model\Sample\Builder::class
            );
        }

        return $this->sampleBuilder;
    }

    /**
     * Get LinkFactory instance
     *
     * @deprecated
     * @return LinkFactory
     */
    private function getLinkFactory()
    {
        if (!$this->linkFactory) {
            $this->linkFactory = $this->_objectManager->get(LinkFactory::class);
        }

        return $this->linkFactory;
    }

    /**
     * Get Sample Factory
     *
     * @deprecated
     * @return SampleFactory
     */
    private function getSampleFactory()
    {
        if (!$this->sampleFactory) {
            $this->sampleFactory = $this->_objectManager->get(SampleFactory::class);
        }

        return $this->sampleFactory;
    }

}
