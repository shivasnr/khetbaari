<?php

namespace Knowband\Marketplace\Controller\Adminhtml\Marketplace;
use Magento\Framework\Controller\ResultFactory;
class GeneralSettings extends \Magento\Backend\App\Action
{
    public $resultPageFactory = false;
    public $mp_request;
    public $mp_resource;
    public $mp_storeManager;
    public $mp_cacheFrontendPool;
    public $mp_cacheTypeList;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Knowband\Marketplace\Helper\Setting $settingHelper,
        \Knowband\Marketplace\Helper\Product $productHelper
    ) {
        parent::__construct($context);
        $this->mp_request = $request;
        $this->resultPageFactory = $resultPageFactory;
        $this->mp_resource = $resource;
        $this->mp_storeManager = $storeManager;
        $this->mp_cacheFrontendPool = $cacheFrontendPool;
        $this->_eventManager = $eventManager;
        $this->mp_cacheTypeList = $cacheTypeList;
        $this->mp_settingsHelper = $settingHelper;
        $this->mp_productHelper = $productHelper;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Knowband_Marketplace::general_settings');
        $resultPage->getConfig()->getTitle()->prepend(__('General Settings'));
        $resultPage->addBreadcrumb(__('Knowband'), __('Knowband'));
        $resultPage->addBreadcrumb(__('Marketplace'), __('Marketplace'));

        if ($this->getRequest()->getParam('store')) {
            $scope_id = $this->mp_storeManager->getStore($this->getRequest()->getParam('store'))->getId();
            $scope = "stores";
        } elseif ($this->getRequest()->getParam('website')) {
            $scope_id = $this->mp_storeManager->getWebsite($this->getRequest()->getParam('website'))->getId();
            $scope = "websites";
        } elseif ($this->getRequest()->getParam('group')) {
            $scope_id = $this->mp_storeManager->getGroup($this->getRequest()->getParam('group'))->getWebsite()->getId();
            $scope = "groups";
        } else {
            $scope = "default";
            $scope_id = 0;
        }
        if ($this->mp_request->isPost()) {
            $post_data = $this->mp_request->getPostValue();
            if (isset($post_data['vss_mp'])) {
                $mp_post_data = $post_data['vss_mp'];
                unset($post_data["form_key"]);
                $selected_store_cat_arr = '';
                $selected_store_categories = '';
                if (empty($selected_store_categories)) {
                    $this->mp_productHelper->updateCatgoryMapping([], \Knowband\Marketplace\Helper\Product::CAT_MAP, 'global');
                }
                $mp_post_data['category_ids'] = $selected_store_categories;
                $value = json_encode($mp_post_data);

                $this->mp_resource->saveConfig("knowband/marketplace/general_settings", $value, $scope, $scope_id);
                
                if (isset($post_data['vss_mp']['enable_mp']) && $post_data['vss_mp']['enable_mp'] == '1') {
                    $this->mp_resource->saveConfig('vss/marketplace/active', 1, $scope, $scope_id);
                } else {
                    $this->mp_resource->saveConfig('vss/marketplace/active', 0, $scope, $scope_id);
                }

                $this->_eventManager->dispatch('marketplace_global_setting_save_after', ['settings' => $post_data]);

                $this->messageManager->addSuccess(__('Settings saved successfully.'));
                $types = ['config'];
                foreach ($types as $type) {
                    $this->mp_cacheTypeList->cleanType($type);
                }
                foreach ($this->mp_cacheFrontendPool as $cacheFrontend) {
                    $cacheFrontend->getBackend()->clean();
                }
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl('*/*/');
                return $resultRedirect;
            }
        }
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Knowband_Marketplace::general_settings');
    }
}
