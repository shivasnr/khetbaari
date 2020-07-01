<?php

/**
 * Knowband_Marketplace
 *
 * @category    Knowband
 * @package     Knowband_Marketplace
 * @author      Knowband Team <support@knowband.com.com>
 * @copyright   Knowband (http://wwww.knowband.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Knowband\Marketplace\Helper;
class Email extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $mp_storeManager;
    protected $mp_scopeConfig;
    protected $mp_request;
    protected $rulesFactory;
    protected $mp_objectManager;
    
    //Email templates
    //Email Template Names
    CONST SELLER_REGISTRATION_NOTIFICATION_ADMIN = 'mp_seller_registration_notification_admin';

    CONST SELLER_WELCOME = 'mp_welcome_seller';
    CONST PRODUCT_DELETE_NOTIFICATION = 'mp_product_delete_notification';        
        

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItem,
        \Knowband\Marketplace\Model\Mail\TransportBuilder $transportBuilder,
        \Knowband\Marketplace\Model\Settings $mpSettingModel,
        \Knowband\Marketplace\Model\Reason $mpReasonModel,
        \Knowband\Marketplace\Model\Seller $mpSellerModel,
        \Knowband\Marketplace\Model\Emailtemplates $mpEmailTemplatesModel,
        \Knowband\Marketplace\Helper\Setting $mpSettingHelper,
        \Knowband\Marketplace\Helper\Log $mpLogger
    )
    {
        $this->mp_storeManager = $storeManager;
        $this->moduleManager = $context->getModuleManager();
        $this->mp_scopeConfig = $context->getScopeConfig();
        $this->mp_request = $context->getRequest();
        $this->mp_resource = $configResource;
        $this->mp_objectManager = $objectManager;
        $this->date = $date;
        $this->_registry = $registry;
        $this->_customer = $customer;
        $this->_producModel = $productModel;
        $this->_stockItem = $stockItem;
        $this->_priceHelper = $priceHelper;
        $this->mp_settingModel = $mpSettingModel;
        $this->mp_reasonModel = $mpReasonModel;
        $this->mp_sellerModel = $mpSellerModel;
        $this->mp_emailTemplateModel = $mpEmailTemplatesModel;
        $this->mp_logHelper = $mpLogger;
        $this->mp_settingHelper = $mpSettingHelper;
        $this->inlineTranslation = $inlineTranslation;
        $this->mp_transportBuilder = $transportBuilder;
        $this->logger = $context->getLogger();
        parent::__construct($context);
    }

    public function getDate() {
        return $this->date->date();
    }

    public function getSellerInfoInAdmin($seller_id) {
        $data = [];
        $seller_collection = $this->mp_sellerModel->getCollection();
        $seller_collection->addFieldToFilter('seller_id', ['eq' => $seller_id]);

        if ($seller_collection->getSize() > 0) {
            $tmp = $seller_collection->getData();
            $data = $tmp[0];
            $customer_collection = $this->_customer->load($seller_id);
            $last_name = $customer_collection->getLastname();
            $customer_data = $customer_collection->getData();
            if (is_array($customer_data) && !empty($customer_data)) {
                $data['name'] = $customer_collection->getFirstname() . ((!empty($last_name)) ? ' ' . $last_name : '');
                $data['email'] = $customer_collection->getEmail();
            } else {
                $data['name'] = '';
                $data['email'] = '';
            }
            unset($customer_collection);
        }
        unset($seller_collection);
        if (!isset($data['shop_title'])) {
            $data['shop_title'] = \Knowband\Marketplace\Helper\Seller::SELLER_DEFAULT_TITLE;
        }
        return $data;
    }

    public function getStoreInfoInAdmin($store_id) {
        return $this->mp_storeManager->getStore($store_id)->getData();
    }

    /**
     * 
     * @param type $customer_data
     */
    public function sendWelcomeSellerEmail($customer_data) {
        return true;
        try {
            $mpTemplateModel = $this->mp_emailTemplateModel->load(self::SELLER_WELCOME, 'template_name');
            $templateData = $mpTemplateModel->getData();
            $mpTemplateModel->unsetData();
            $emailTemplateVariables = [];
            $email = $customer_data['email'];
            $name = $customer_data['firstname'] . ' ' . $customer_data['lastname'];
            $templateData['template_content'] = str_replace('{{var email}}', $email, (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var full_name}}', $name, (string) $templateData['template_content']);
            $emailTemplateVariables['email_content'] = (string) $templateData['template_content'];
            $emailTemplateVariables['templateSubject'] = $templateData['template_subject'];

            $senderName = $this->getStoreName();
            $senderEmail = $this->getStoreEmail();
            $sender = ['name' => $senderName, 'email' => $senderEmail];

            $this->inlineTranslation->suspend();
            $_transportBuilder = $this->mp_transportBuilder;
            // clear previous data first.
            $_transportBuilder->clearFrom();
            $_transportBuilder->clearSubject();
            $_transportBuilder->clearMessageId();
            $_transportBuilder->clearBody();
            $_transportBuilder->clearRecipients();
            $_transportBuilder->setTemplateIdentifier('kb_marketplace_email_template')
                    ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
                    ->setTemplateVars($emailTemplateVariables)
                    ->setFrom($sender)
                    ->addTo($email)
                    ->setReplyTo($senderEmail);
            $transport = $_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $ex) {
            $this->logger->critical($ex->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Email::sendWelcomeSellerEmail()', $ex->getMessage()
            );
        }
    }

    /**
     * 
     * @param type $customer_data
     */
    public function sendSellerRegistrationNotificationEmail($customer_data) {
        try {
            $mpTemplateModel = $this->mp_emailTemplateModel->load(self::SELLER_REGISTRATION_NOTIFICATION_ADMIN, 'template_name');
            $templateData = $mpTemplateModel->getData();
            $mpTemplateModel->unsetData();
            $emailTemplateVariables = [];
            $templateData['template_content'] = str_replace('{{var customer_email}}', $customer_data['email'], (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var full_name}}', $customer_data['firstname'] . ' ' . $customer_data['lastname'], (string) $templateData['template_content']);
            $emailTemplateVariables['email_content'] = (string) $templateData['template_content'];

            unset($customer_data);

            $emailTemplateVariables['templateSubject'] = $templateData['template_subject'];

            $senderName = $this->getStoreName();
            $senderEmail = $this->getStoreEmail();
            $sender = ['name' => $senderName, 'email' => $senderEmail];

            $this->inlineTranslation->suspend();
            $_transportBuilder = $this->mp_transportBuilder;
            // clear previous data first.
            $_transportBuilder->clearFrom();
            $_transportBuilder->clearSubject();
            $_transportBuilder->clearMessageId();
            $_transportBuilder->clearBody();
            $_transportBuilder->clearRecipients();
            $_transportBuilder->setTemplateIdentifier('kb_marketplace_email_template')
                    ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
                    ->setTemplateVars($emailTemplateVariables)
                    ->setFrom($sender)
                    ->addTo($senderEmail)
                    ->setReplyTo($senderEmail);
            $transport = $_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $ex) {
            $this->logger->critical($ex->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Email::sendSellerRegistrationNotificationEmail()', $ex->getMessage()
            );
        }
    }

    public function sendDeleteProductEmailtoSeller($seller_id, $product_id, $reason = '') {
        try {
            $seller_info = $this->getSellerInfoInAdmin($seller_id);

            $mpTemplateModel = $this->mp_emailTemplateModel->load(self::PRODUCT_DELETE_NOTIFICATION, 'template_name');
            $templateData = $mpTemplateModel->getData();
            $mpTemplateModel->unsetData();

            $product = $this->_producModel->load($product_id);
            try{
                $qty = $this->getQuantity($product_id);
            } catch (\Exception $ex) {
                $qty = 'NA';
                $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Email::sendDeleteProductEmailtoSeller()', $ex->getMessage()
                );
            }

            $contact = '';
            if (isset($seller_info['contact_number']) && !empty($seller_info['contact_number'])) {
                $contact = $seller_info['contact_number'];
            }

            $emailTemplateVariables = [];
            $templateData['template_content'] = str_replace('{{var product_name}}', $product->getName(), (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var product_sku}}', $product->getSku(), (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var product_price}}', $this->_priceHelper->currency($product->getPrice(), true, false), (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var product_qty}}', $qty, (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var shop_title}}', $seller_info['shop_title'], (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var seller_name}}', $seller_info['name'], (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var seller_email}}', $seller_info['email'], (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var seller_contact}}', $contact, (string) $templateData['template_content']);
            $templateData['template_content'] = str_replace('{{var reason}}', $reason, (string) $templateData['template_content']);

            $emailTemplateVariables['email_content'] = (string) $templateData['template_content'];
            $emailTemplateVariables['templateSubject'] = str_replace('{{store_name}}', $this->getStoreName(), (string) $templateData['template_subject']);

            $senderName = $this->getStoreName();
            $senderEmail = $this->getStoreEmail();
            $sender = ['name' => $senderName, 'email' => $senderEmail];

            $to_email = $seller_info['email'];

            $product->unsetData();

            $this->inlineTranslation->suspend();
            $_transportBuilder = $this->mp_transportBuilder;
            // clear previous data first.
            $_transportBuilder->clearFrom();
            $_transportBuilder->clearSubject();
            $_transportBuilder->clearMessageId();
            $_transportBuilder->clearBody();
            $_transportBuilder->clearRecipients();
            $_transportBuilder->setTemplateIdentifier('kb_marketplace_email_template')
                    ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
                    ->setTemplateVars($emailTemplateVariables)
                    ->setFrom($sender)
                    ->addTo($to_email)
                    ->setReplyTo($senderEmail);
            $transport = $_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $ex) {
            $this->logger->critical($ex->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Email::sendDeleteProductEmailtoSeller()', $ex->getMessage()
            );
        }
    }

    public function sendOrderNotificationToSeller($seller_id, $email_data) {        
        try {
            $seller_info = $this->getSellerInfoInAdmin($seller_id);
            $order = $email_data['order'];
            $emailTemplateVariables = [];
            $emailTemplateVariables['order'] = $order;
            $emailTemplateVariables['billing'] = $order->getBillingAddress();
            $emailTemplateVariables['payment_html'] = $this->getPaymentHtml($order);
            $emailTemplateVariables['store'] = $order->getStore();
            $emailTemplateVariables['formattedShippingAddress'] = $this->getFormattedShippingAddress($order);
            $emailTemplateVariables['formattedBillingAddress'] = $this->getFormattedBillingAddress($order);
            $emailTemplateVariables['seller_name'] = $seller_info['name'];
            
            $senderName = $this->getStoreName();
            $senderEmail = $this->getStoreEmail();
            $sender = ['name' => $senderName, 'email' => $senderEmail];

            $this->inlineTranslation->suspend();
            $_transportBuilder = $this->mp_transportBuilder;
            // clear previous data first.
            $_transportBuilder->clearFrom();
            $_transportBuilder->clearSubject();
            $_transportBuilder->clearMessageId();
            $_transportBuilder->clearBody();
            $_transportBuilder->clearRecipients();
            $_transportBuilder->setTemplateIdentifier('kb_marketplace_seller_order_notification_email_template')
                    ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
                    ->setTemplateVars($emailTemplateVariables)
                    ->setFrom($sender)
                    ->addTo($seller_info['email'])
                    ->setReplyTo($senderEmail);
            $transport = $_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $ex) {
            $this->logger->critical($ex->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Email::sendOrderNotificationToSeller()', $ex->getMessage()
            );
        }
    }
    
    /**
     * Returns payment info block as HTML.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return string
     */
    private function getPaymentHtml(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $paymentHelper = $this->mp_objectManager->get("\Magento\Payment\Helper\Data");
        $identityContainer = $this->mp_objectManager->get("\Magento\Sales\Model\Order\Email\Container\CreditmemoIdentity");
        return $paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $identityContainer->getStore()->getStoreId()
        );
    }
    
    /**
     * @param Order $order
     * @return string|null
     */
    private function getFormattedShippingAddress($order)
    {
        $addressRenderer = $this->mp_objectManager->get("\Magento\Sales\Model\Order\Address\Renderer");
        return $order->getIsVirtual()
            ? null
            : $addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * @param Order $order
     * @return string|null
     */
    private function getFormattedBillingAddress($order)
    {
        $addressRenderer = $this->mp_objectManager->get("\Magento\Sales\Model\Order\Address\Renderer");
        return $addressRenderer->format($order->getBillingAddress(), 'html');
    }
    
    public function getSalesName() {
        return $this->mp_scopeConfig->getValue(
                        'trans_email/ident_sales/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSalesEmail() {
        return $this->mp_scopeConfig->getValue(
                        'trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getStoreName() {
        return $this->mp_scopeConfig->getValue(
                        'trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getStoreEmail() {
        return $this->mp_scopeConfig->getValue(
                        'trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBaseUrl() {
        return $this->mp_storeManager->getStore()->getBaseUrl();
    }

    //Low Stock Starts    
    public function getAdminEmailAndName() {
        $pluginSettings = $this->mp_settingHelper->getSettings();
        $admin['email'] = $this->mp_scopeConfig->getValue('trans_email/ident_' . $pluginSettings['notification_emails'] . '/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $admin['name'] = $this->mp_scopeConfig->getValue('trans_email/ident_' . $pluginSettings['notification_emails'] . '/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $admin;
    }
    
    public function getQuantity($productId = 0){
        try{
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $stockStatusCriteriaFactory = $objectManager->get("\Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory");
             /** @var \Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory **/
            $criteria = $stockStatusCriteriaFactory->create();
            $criteria->setProductsFilter($productId);
            /** @var \Magento\CatalogInventory\Api\Data\StockStatusRepositoryInterface $stockStatusRepository **/
            $stockStatusRepository = $objectManager->get("\Magento\CatalogInventory\Api\StockStatusRepositoryInterface");
            $result = $stockStatusRepository->getList($criteria);
            $stockStatus = current($result->getItems());          // product id
            if($stockStatus){
                $qty = $stockStatus->getQty();
            } else{
                $qty = 0;
            }
            unset($criteria);
            return $qty;
        } catch (\Exception $ex) {
            $this->logger->critical($ex->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Email::getQuantity()', $ex->getMessage()
            );
            return 0;
        }
    }
}
