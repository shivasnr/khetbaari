<?php

namespace Knowband\Marketplace\Controller\Adminhtml\Marketplace;

use Magento\Framework\Controller\ResultFactory;
class EditEmailTemplate extends \Magento\Backend\App\Action
{
    public $resultPageFactory = false;
    public $mp_request;
    public $mp_resource;
    public $mp_storeManager;
    public $mp_cacheFrontendPool;
    public $mp_cacheTypeList;
    protected $mp_helper;
    private $logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Knowband\Marketplace\Model\Emailtemplates $mpEmailTemplates,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Knowband\Marketplace\Helper\Log $mpLogHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->mp_request = $request;
        $this->resultPageFactory = $resultPageFactory;
        $this->mp_storeManager = $storeManager;
        $this->mp_cacheFrontendPool = $cacheFrontendPool;
        $this->mp_cacheTypeList = $cacheTypeList;
        $this->mp_emailTemplates = $mpEmailTemplates;
        $this->mp_logHelper = $mpLogHelper;
        $this->date = $date;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Knowband_Marketplace::email_templates');
        $resultPage->addBreadcrumb(__('Knowband'), __('Knowband'));
        $resultPage->addBreadcrumb(__('Marketplace'), __('Marketplace'));
        
        $resultPage->getConfig()->getTitle()->prepend(__('Edit Email Template'));
        
        if ($this->getRequest()->getParam('template_id')) {
            if ($this->mp_request->isPost()) {
                try {
                    $post_data = $this->getRequest()->getPost();
                    if (isset($post_data['mpEmailTemplate'])) {
                        $template_data = $post_data['mpEmailTemplate'];
                        $template_data['updated_at'] = $this->date->date();
                        $model = $this->mp_emailTemplates;
                        $model->load($template_data['id']);
                        $data = $model->getData();
                        if (!empty($data)) {
                            $model->addData($template_data);
                            $model->setId($template_data['id'])->save();
                            $model->unsetData();
                            $this->messageManager->addSuccess(__('Email Template Updated successfully.'));
                        }

                        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                        $resultRedirect->setPath('mpadmin/marketplace/emailtemplates');
                        return $resultRedirect;
                    }
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                    $this->mp_logHelper->createFileAndWriteLogData(
                            \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller EditEmailTemplate::execute()', $e->getMessage()
                    );
                    $this->messageManager->addError($e->getMessage());
                    $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    $resultRedirect->setPath($this->_redirect->getRefererUrl());
                    return $resultRedirect;
                }
            }
        }else {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Knowband_Marketplace::email_templates');
    }
}
