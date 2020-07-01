<?php

namespace Knowband\Marketplace\Controller\Profile;

use Knowband\Marketplace\Controller\Index\ParentController;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
class Index extends ParentController {

    protected $mp_resultRawFactory;
    protected $mp_request;
    protected $mp_scopeConfig;
    protected $inlineTranslation;
    protected $mp_transportBuilder;

    public function __construct(
            \Magento\Framework\App\Action\Context $context, 
            \Magento\Framework\App\Request\Http $request, 
            \Magento\Framework\App\Response\Http $response,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Filesystem $fileSystem,
            \Magento\Customer\Model\Session $customerSessionModel,
            \Magento\Framework\View\Result\PageFactory $resultRawFactory,
            \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
            \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
            \Knowband\Marketplace\Model\Seller $sellerModel,
            \Knowband\Marketplace\Helper\Setting $settingHelper,
            \Knowband\Marketplace\Helper\Seller $sellerHelper,
            \Knowband\Marketplace\Helper\Log $logHelper
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_cacheFrontendPool = $cacheFrontendPool;
        $this->mp_cacheTypeList = $cacheTypeList;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_logHelper = $logHelper;
        $this->mp_sellerModel = $sellerModel;
        $this->_filesystem = $fileSystem;
    }

    public function execute() {
        
        $this->isLoggedIn();
        $resultPage = $this->mp_resultRawFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Seller Profile'));
        try {
            $post_data = $this->mp_request->getPost();
            if ($this->mp_request->isPost() && !empty($post_data)) {
                $profile_data = $post_data;
                $uploadBanner = true;
                $uploadlogo = true;
                if (isset($profile_data['mp_frontProfile']['remove_logo']) && $profile_data['mp_frontProfile']['remove_logo'] == 1) {
                    $this->removeLogoAction($profile_data['mp_frontProfile']['seller_id']);
                    $uploadlogo = false;
                }

                if (isset($profile_data['mp_frontProfile']['remove_banner']) && $profile_data['mp_frontProfile']['remove_banner'] == 1) {
                    $this->removeBannerAction($profile_data['mp_frontProfile']['seller_id']);
                    $uploadBanner = false;
                }

                $mediaUrl = $this->mp_settingHelper->getMediaUrl();
                 
                $mediaDirectory = $mediaUrl .'Knowband_Marketplace';

                $mpLogo_Img = $this->getRequest()->getFiles('mplogo_img');
                if (isset($mpLogo_Img['name']) && ( file_exists($mpLogo_Img['tmp_name'])) && $uploadlogo) {
                    $path = $mediaDirectory . '/Seller_' . $this->_customerInfo['entity_id'];
                    $mask = $path . '/logo.*';
                    $matches = glob($mask);
                    if (!empty($matches)){
                        array_map('unlink', $matches);
                    }
                    
                    $ext = explode('.', $mpLogo_Img['name']);
                    $ext = array_pop($ext);
                    $mpLogo_Img['name'] = 'logo.'.$ext;
                    $uploader = $this->_objectManager->create(
                                    '\Magento\MediaStorage\Model\File\Uploader',
                                    ['fileId' => $mpLogo_Img]
                                );
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
                    $uploader->setFilesDispersion(false);
                    $imageAdapterFactory = $this->_objectManager->get('Magento\Framework\Image\AdapterFactory')
                        ->create();
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setAllowCreateFolders(true);
                    $mediaDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
                    $uploader->save(
                        $mediaDirectory
                            ->getAbsolutePath('Knowband_Marketplace'.'/Seller_' . $this->_customerInfo['entity_id'])
                    );
                    
                    $logoUrl = $mediaUrl. 'Knowband_Marketplace'. '/Seller_' . $this->_customerInfo['entity_id']. "/" . 'logo.' . $ext;
                    $profile_data['logo'] = $logoUrl;
                }
                
                $mpBanner_Img = $this->getRequest()->getFiles('mpbanner_img');
                if (isset($mpBanner_Img['name']) && ( file_exists($mpBanner_Img['tmp_name'])) && $uploadBanner) {
                    $path = $mediaUrl .'Knowband_Marketplace' . '/Seller_' . $this->_customerInfo['entity_id'];
                    $mask = $path . '/banner.*';
                    $matches = glob($mask);
                    if (!empty($matches)){
                        array_map('unlink', $matches);
                    }
                    
                    $ext = explode('.', $mpBanner_Img['name']);
                    $ext = array_pop($ext);
                    $mpBanner_Img['name'] = 'banner.'.$ext;
                    $uploader = $this->_objectManager->create(
                                    '\Magento\MediaStorage\Model\File\Uploader',
                                    ['fileId' => $mpBanner_Img]
                                );
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
                    $uploader->setFilesDispersion(false);
                    $imageAdapterFactory = $this->_objectManager->get('Magento\Framework\Image\AdapterFactory')
                        ->create();
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setAllowCreateFolders(true);
                    $mediaDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
                    $uploader->save(
                        $mediaDirectory
                            ->getAbsolutePath('Knowband_Marketplace'.'/Seller_' . $this->_customerInfo['entity_id'])
                    );
                    
                    
                    $bannerUrl = $mediaUrl. 'Knowband_Marketplace'. '/Seller_' . $this->_customerInfo['entity_id']. "/" . 'banner.' . $ext;
                    $profile_data['banner'] = $bannerUrl;
                }
                $mpDataHelper = $this->_objectManager->create("\Knowband\Marketplace\Helper\Data");
                $mpDataHelper->saveSellerProfileData($profile_data, $this->_customerInfo['entity_id']);
                $this->messageManager->addSuccess(__('Seller Profile saved successfully.'));
                
                //clear the cache
                $types = ['config'];
                foreach ($types as $type) {
                    $this->mp_cacheTypeList->cleanType($type);
                }
                foreach ($this->mp_cacheFrontendPool as $cacheFrontend) {
                    $cacheFrontend->getBackend()->clean();
                }
                
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                return $resultRedirect;
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Profile\Index::execute()', $ex->getMessage()
            );
            $this->messageManager->addError($ex->getMessage());
        }
        return $resultPage;
    }
    
    public function removeLogoAction($seller_id) {
        $this->isLoggedIn();
        try {
            if ($seller_id > 0) {
                $sellerModel = $this->mp_sellerModel->load($seller_id, 'seller_id');
                $logoUrl = $sellerModel->getShopLogo();
                $logoPathArray = explode('/', $logoUrl);
                $logoFileName = end($logoPathArray);

                $logoPath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('Knowband_Marketplace'.'/Seller_' . $this->_customerInfo['entity_id']). "/" . $logoFileName;
                if (file_exists($logoPath) && !is_dir($bannerPath)) {
                    if (unlink($logoPath)) {
                        $data['shop_logo'] = '';
                        $entity_id = $sellerModel->getSellerEntityId();
                        $sellerModel->addData($data);
                        $sellerModel->setId($entity_id)->save();
                    }
                }
                $sellerModel->unsetData();
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Profile\Index::removeLogoAction()', $ex->getMessage()
            );
            $this->messageManager->addError($ex->getMessage());
        }
    }

    public function removeBannerAction($seller_id) {
        $this->isLoggedIn();
        try {
            if ($seller_id > 0) {
                $sellerModel = $this->mp_sellerModel->load($seller_id, 'seller_id');
                $bannerUrl = $sellerModel->getShopBanner();
                $bannerPathArray = explode('/', $bannerUrl);
                $bannerFileName = end($bannerPathArray);

                $bannerPath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('Knowband_Marketplace'.'/Seller_' . $this->_customerInfo['entity_id']) . "/" . $bannerFileName;
                if (file_exists($bannerPath) && !is_dir($bannerPath)) {
                    if (unlink($bannerPath)) {
                        $data['shop_banner'] = '';
                        $entity_id = $sellerModel->getSellerEntityId();
                        $sellerModel->addData($data);
                        $sellerModel->setId($entity_id)->save();
                    }
                }
                $sellerModel->unsetData();
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Profile\Index::removeBannerAction()', $ex->getMessage()
            );
            $this->messageManager->addError($ex->getMessage());
        }
    }

}
