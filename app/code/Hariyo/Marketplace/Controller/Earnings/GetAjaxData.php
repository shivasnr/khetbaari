<?php

namespace Hariyo\Marketplace\Controller\Earnings;

use Hariyo\Marketplace\Controller\Index\ParentController;
class GetAjaxData extends ParentController {

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
            \Magento\Customer\Model\Session $customerSessionModel,
            \Magento\Framework\View\Result\PageFactory $resultRawFactory,
            \Hariyo\Marketplace\Helper\Setting $settingHelper,
            \Hariyo\Marketplace\Helper\Seller $sellerHelper,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Magento\Framework\View\LayoutInterface $layout
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_layout = $layout;
    }

    public function execute() {
        $this->isLoggedIn();
        $action = $this->mp_request->getParam("action");
        $json = [];
        $data = ['count' => 0, 'collection' => []];
        if ($action == 'history') {
            $data = $this->_layout->getBlockSingleton(\Hariyo\Marketplace\Block\Earnings\History::class)->getList();
        } else if ($action == 'orderwise') {
            $data = $this->_layout->getBlockSingleton(\Hariyo\Marketplace\Block\Earnings\Orderwise::class)->getList();
        } else if ($action == 'transactionhistory') {
            $data = $this->_layout->getBlockSingleton(\Hariyo\Marketplace\Block\Earnings\Transactions::class)->getList();
        }
         $json = [
            "draw" => intval($this->getRequest()->getParam('draw')),
            "recordsTotal" => intval($data['count']),
            "recordsFiltered" => intval($data['count']),
            "data" => $data['collection']
        ];
        $result = $this->resultJsonFactory->create();
        return $result->setData($json);
    }

}
