<?php

namespace Hariyo\Marketplace\Controller\Order;

use Hariyo\Marketplace\Controller\Index\ParentController;
use Magento\Framework\App\Filesystem\DirectoryList;
class Pdfinvoices extends ParentController {

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
            \Hariyo\Marketplace\Model\Seller $selleModel,
            \Magento\Sales\Api\OrderManagementInterface $orderManagement,
            \Magento\Sales\Model\Order\Pdf\Invoice $invoice,
            \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
            \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
            \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
            \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->_coreRegistry = $registry;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_sellerModel = $selleModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;
        $this->orderManagement = $orderManagement;
        $this->pdfInvoice = $invoice;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->fileFactory = $fileFactory;
        $this->dateTime = $dateTime;
    }

    public function execute() {
        $this->isLoggedIn();
        $id = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderFactory->create()->load($id);
            if($order){
                $invoicesCollection = $this->invoiceCollectionFactory->create()->setOrderFilter(['eq' => $order->getId()]);
                if (!$invoicesCollection->getSize()) {
                    $this->messageManager->addError(__('There are no invoice documents related to this order.'));
                    return $this->resultRedirectFactory->create()->setPath('marketplace/order/orderview', ['order_id' => $order->getId()]);
                }
                return $this->fileFactory->create(
                    sprintf('invoice%s.pdf', $this->dateTime->date('Y-m-d_H-i-s')),
                    $this->pdfInvoice->getPdf($invoicesCollection->getItems())->render(),
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            } else{
                $this->messageManager->addError(__('Requested order details not Found.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Requested order details not Found.'));
        }
        return $this->resultRedirectFactory->create()->setPath('marketplace/order/orderlist');
    }
}
