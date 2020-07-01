<?php
namespace Hariyo\Marketplace\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class SaveCustomerAsSellerData implements ObserverInterface
{    
    
    public function __construct(
            \Magento\Framework\App\Helper\Context $context,
            \Magento\Framework\App\Request\Http $request,
            \Magento\Framework\Filesystem $fileSystem,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Event\Manager $eventManager,
            \Hariyo\Marketplace\Helper\Setting $mpSettingHelper,
            \Hariyo\Marketplace\Helper\Data $mpDataHelper,
            \Hariyo\Marketplace\Helper\Log $mpLogHelper
            ) {
        $this->_logger = $context->getLogger();
        $this->mp_request = $request;
        $this->_filesystem = $fileSystem;
        $this->_objectManager = $objectManager;
        $this->mp_settingHelper = $mpSettingHelper;
        $this->mp_dataHelper = $mpDataHelper;
        $this->mp_logHelper = $mpLogHelper;
        $this->_eventManager = $eventManager;
        $this->messageManager = $this->_objectManager->get("\Magento\Framework\Message\ManagerInterface");
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer) {
        try {
            $post_data = $this->mp_request->getPostValue();
            $customer = $observer->getEvent()->getCustomer();

            if (isset($post_data['mp_general']['commission'])) {
                $productCount = $this->mp_settingHelper->countSellerProducts((int) $customer->getId());
                $storedLimit = $this->mp_settingHelper->getSettingByKey((int) $customer->getId(), 'product_limit');
                if (isset($post_data['mp_general']['product_limit']['global']) && !$post_data['mp_general']['product_limit']['global']) {
                    if ((isset($post_data['mp_general']['product_limit']['seller']) && ($post_data['mp_general']['product_limit']['seller'] < $productCount)) || (($productCount > $storedLimit) && (isset($post_data['mp_general']['product_limit']['seller']) && ($post_data['mp_general']['product_limit']['seller'] < $storedLimit)))) {
                        $this->messageManager->addError(__('The seller has already added more products. Please either increase the limit or save it as it is.'));
                    }
                }
                $settings = $post_data['mp_general'];
                
                $this->_eventManager->dispatch('marketplace_seller_setting_save_before', ['settings' => $settings]);
                $this->mp_dataHelper->saveSellerSettings($settings, $customer->getId());

                $this->_eventManager->dispatch('marketplace_seller_setting_save_after', ['settings' => $settings]);

                unset($post_data);
            } else {
                if (isset($post_data['mp_general']['register_as_seller']) && $post_data['mp_general']['register_as_seller'] == 'yes') {
                    $customer_data = $post_data['customer'];
                    $customer_data['entity_id'] = $customer->getId();
                    if ($customer_data['website_id'] != 0) {
                        $this->_eventManager->dispatch('marketplace_seller_register_before', ['seller_data' => &$customer_data]);
                        $this->mp_dataHelper->saveInitialSellerData($customer_data, true);
                        $this->_eventManager->dispatch('marketplace_seller_register_after', ['seller_data' => $customer_data]);
                    }
                    unset($customer_data);
                }
            }

        return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Observer SaveCustomerAsSellerData::execute()', $e->getMessage()
            );
        }
    }
}
