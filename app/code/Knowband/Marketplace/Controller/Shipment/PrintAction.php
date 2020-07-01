<?php

namespace Knowband\Marketplace\Controller\Shipment;

use Knowband\Marketplace\Controller\Index\ParentController;
use Magento\Framework\App\Filesystem\DirectoryList;
class PrintAction extends ParentController {

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
            \Knowband\Marketplace\Helper\Setting $settingHelper,
            \Knowband\Marketplace\Helper\Seller $sellerHelper,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Knowband\Marketplace\Model\Seller $selleModel,
            \Magento\Sales\Model\OrderFactory $orderFactory,
            \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
            \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->_registry = $registry;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_sellerModel = $selleModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;
        $this->shipmentLoader = $shipmentLoader;
        $this->_fileFactory = $fileFactory;
    }

    public function execute() {
        $this->isLoggedIn();

        $shipmentId = $this->getRequest()->getParam('shipment_id');
        if ($shipmentId) {
            $shipment = $this->_objectManager->create('Magento\Sales\Model\Order\Shipment')->load($shipmentId);
            if ($shipment) {
                $pdf = $this->_objectManager->create(
                                'Magento\Sales\Model\Order\Pdf\Shipment'
                        )->getPdf(
                        [$shipment]
                );
                $date = $this->_objectManager->get('Magento\Framework\Stdlib\DateTime\DateTime')->date('Y-m-d_H-i-s');
                return $this->_fileFactory->create(
                                'packingslip' . $date . '.pdf', $pdf->render(), DirectoryList::VAR_DIR, 'application/pdf'
                );
            }
        } else {
            /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }
    }

}
