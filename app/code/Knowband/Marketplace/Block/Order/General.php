<?php

namespace Knowband\Marketplace\Block\Order;

use Magento\Sales\Model\Order\Address;
class General extends \Magento\Framework\View\Element\Template {
    
    protected $order = null;
    protected $_totals;
    protected $_itemRenders = [];
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Magento\Catalog\Model\Product $productModel,
            \Magento\Framework\Event\Manager $eventManager,
            \Knowband\Marketplace\Model\Seller $mpSellerModel,
            \Knowband\Marketplace\Model\Shipments $mpShipmentModel,
            \Knowband\Marketplace\Helper\Data $mpDataHelper,
            \Knowband\Marketplace\Helper\Log $mpLogHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        $this->_storeManager = $context->getStoreManager();
        $this->_timezone = $timezone;
        $this->_eventManager = $eventManager;
        $this->_configScopeConfigInterface = $context->getScopeConfig();
        $this->_productModel = $productModel;
        $this->mp_dataHelper = $mpDataHelper;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_sellerModel = $mpSellerModel;
        $this->mp_shipmentModel = $mpShipmentModel;
        $this->addressRenderer = $objectManager->get("\Magento\Sales\Model\Order\Address\Renderer");
        
        parent::__construct($context);
        $this->order = $this->_coreRegistry->registry('current_order');
    }
    
    /**
     * Retrieve available order
     *
     * @return Order
     */
    public function getOrder() {
        return $this->order;
    }
    
    /**
     * Retrieve shipment
     *
     * @return shipment
     */
    public function getShipment() {
        return $this->_coreRegistry->registry('current_shipment');
    }

    /**
     * Retrieve credit memo
     *
     * @return creditmemo
     */
    public function getCreditmemo() {
        return $this->_coreRegistry->registry('current_creditmemo');
    }

    public function getEntity() {
        if ($this->getRequest()->getControllerName() == 'shipment') {
            return $this->getShipment();
        } elseif ($this->getRequest()->getControllerName() == 'creditmemo') {
            return $this->getCreditmemo();
        }
        return $this->getOrder();
    }

    public function setItem(\Magento\Framework\DataObject $item) {
        $this->setData('item', $item);
        return $this;
    }

    public function getItem() {
        return $this->_getData('item');
    }

    public function getOrderItemOrder() {
        return $this->getOrderItem()->getOrder();
    }

    public function getOrderItem() {
        if ($this->getItem() instanceof \Magento\Sales\Model\Order\Item) {
            return $this->getItem();
        } else {
            return $this->getItem()->getOrderItem();
        }
    }

    public function addItemRender($type, $block, $template) {
        $this->_itemRenders[$type] = [
            'block' => $block,
            'template' => $template,
            'renderer' => null
        ];

        return $this;
    }

    public function getItemRenderer($type) {
        if (!isset($this->_itemRenders[$type])) {
            $type = 'default';
        }
        if (is_null($this->_itemRenders[$type]['renderer'])) {
            $this->_itemRenders[$type]['renderer'] = $this->getLayout()
                    ->createBlock($this->_itemRenders[$type]['block'])
                    ->setTemplate($this->_itemRenders[$type]['template'])
                    ->setRenderedBlock($this);
        }
        return $this->_itemRenders[$type]['renderer'];
    }

    /**
     * Prepare item before output
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $renderer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _prepareItem(\Magento\Framework\View\Element\AbstractBlock $renderer)
    {
        return $this;
    }

    /**
     * Return product type for quote/order item
     *
     * @param \Magento\Framework\DataObject $item
     * @return string
     */
    protected function _getItemType(\Magento\Framework\DataObject $item)
    {
        if ($item->getOrderItem()) {
            $type = $item->getOrderItem()->getProductType();
        } elseif ($item instanceof \Magento\Quote\Model\Quote\Address\Item) {
            $type = $item->getQuoteItem()->getProductType();
        } else {
            $type = $item->getProductType();
        }
        return $type;
    }

    
    /**
     * Get item row html
     *
     * @param   \Magento\Framework\DataObject $item
     * @return  string
     */
    public function getItemHtml(\Magento\Framework\DataObject $item)
    {
//        $type = $this->_getItemType($item);

        
        if ($item->getOrderItem()) {
            $type = $item->getOrderItem()->getProductType();
        } else {
            $type = $item->getProductType();
        }

        if($type == 'bundle'){
            
        } else if($type == 'downloadable'){
            
        } else if($type == 'grouped'){
            
        } else {
            $block= $this->getLayout()->createBlock('Knowband\Marketplace\Block\Order\Item\Renderer\DefaultRenderer')->setItem($item)->setTemplate('order/view/items/default.phtml');
        }
        
//        $block = $this->getItemRenderer($type)->setItem($item);
        $this->_prepareItem($block);
        return $block->toHtml();
    }

    /**
     * Get the seller items (array of product id)
     *
     * @return  array
     */
    public function getSellerItems() {
        return $this->mp_sellerModel->getData('seller_items');
    }

    /**
     * Check if the product is of any seller or not
     *
     * @param   string $sku
     * @return  boolean
     */
    public function isSellerProduct($sku = '') {
        $is_seller_product = false;
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $product = $this->_productModel->getCollection();
        $product->getSelect()->join(['s2p' => $product->getTable('vss_mp_product_to_seller')], 'e.entity_id = s2p.product_id');
        $product->getSelect()->where('e.sku="' . $sku . '" and s2p.seller_id=' . $seller_info['entity_id']);
        if ($product->getSize() > 0) {
            $is_seller_product = true;
        }
        unset($product);
        return $is_seller_product;
    }

    /**
     * Get order number
     *
     * @return  string
     */
    public function getOrderNumber() {
        if ($_extOrderId = $this->getOrder()->getExtOrderId()) {
            $_extOrderId = ' [' . $_extOrderId . '] ';
        } else {
            $_extOrderId = '';
        }
        return $this->getOrder()->getRealOrderId() . $_extOrderId;
    }

    public function getOrderDate() {
        return $this->getFormattedDate($this->getOrder()->getCreatedAt());
    }

    public function getFormattedDate($date_value, $format = \IntlDateFormatter::MEDIUM, $show_time = true) {
        return $this->_timezone->formatDate($date_value, $format, $show_time);
    }

    public function getOrderStatus() {
        return $this->getOrder()->getStatusLabel();
    }

    /**
     * Get the general information of order
     *
     * @return  array()
     */
    public function getGeneralInfo() {
        $data = [];
        try {
            $data[] = ['label' => __('Customer'), 'value' => $this->getOrder()->getCustomerName()];
            $data[] = ['label' => __('Email'), 'value' => $this->getOrder()->getCustomerEmail()];
            if (!$this->getOrder()->getIsVirtual()) {
                $value = $this->getOrder()->getShippingDescription();
                if ($this->getRequest()->getControllerName() == 'shipment' && $this->getRequest()->getActionName() == 'view') {

                    if ($this->getShipment()->getTracksCollection()->count()) {

                        $shipping_charge = '<br>' . __('Shipping Charges') . ' - ';

                        if ($this->_objectManager->get('\Magento\Tax\Helper\Data')->displayShippingPriceIncludingTax()) {
                            $_excl = $this->displayShippingPriceInclTax($this->order);
                        } else {
                            $_excl = $this->displayPriceAttribute('shipping_amount', false, ' ');
                        }

                        $shipping_charge .= $_excl;
                        $_incl = $this->displayShippingPriceInclTax($this->order);

                        if ($this->_objectManager->get('\Magento\Tax\Helper\Data')->displayShippingBothPrices() && $_incl != $_excl) {
                            $shipping_charge .= '<br>(' . __('Incl. Tax') . ': ' . $_incl . ')';
                        }
                        $value .= $shipping_charge;
                        $value .= '<br><a href="javascript:void(0)" id="linkId" onclick="popWin(\'' . $this->_objectManager->get('\Magento\Shipping\Helper\Data')->getTrackingPopupUrlBySalesModel($this->getShipment()) . '\', \'trackshipment\', \'width=800,height=600,resizable=yes,scrollbars=yes\')" title="' . __('Track this shipment') . '" >' . __('Track this shipment') . '</a>';
                    }
                } else {
                    $show_tracking_link = false;
                    $seller_info = $this->mp_dataHelper->getSellerInfo();
                    $shipments = $this->getOrder()->getShipmentsCollection();
                    foreach ($shipments as $shipment) {
                        $seller_shipment = $this->mp_shipmentModel->getCollection();
                        $seller_shipment->addFieldToFilter('seller_id', (int) $seller_info['entity_id']);
                        $seller_shipment->addFieldToFilter('shipment_id', (int) $shipment->getId());
                        if (!empty($seller_shipment->getData())) {
                            $show_tracking_link = true;
                            break;
                        }
                        unset($seller_shipment);
                    }
                    if ($show_tracking_link) {
                        $value .= '<br><a href="javascript:void(0)" id="linkId" onclick="popWin(\'' . $this->_objectManager->get('\Magento\Shipping\Helper\Data')->getTrackingPopupUrlBySalesModel($this->order) . '\', \'trackorder\', \'width=800,height=600,resizable=yes,scrollbars=yes\')" title="' . __('Track Order') . '" >' . __('Track Order') . '</a>';
                    }
                }
                $data[] = ['label' => __('Shipping Method'), 'value' => $value];
            } else {
                $data[] = ['label' => __('Shipping Method'), 'value' => __('Shipping not Required')];
            }
            $data[] = ['label' => __('Payment Method'), 'value' => $this->getOrder()->getPayment()->getMethodInstance()->getTitle()];

            $this->_eventManager->dispatch('marketplace_seller_orderorder_general_before', ['general_data' => &$data]);
        } catch (\Exception $e) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Block General::getGeneralInfo()', $e->getMessage()
            );
        }

        return $data;
    }

    public function getBillingAddress() {
        return $this->getOrder()->getBillingAddress()->getFormated(true);
    }

    protected function _initTotals() {
        $source = $this->getEntity();

        $grand_total_title = __('Grand Total');

        if (!$source->getIsVirtual()) {
            $grand_total_title = __('Grand Total (Excl. Shipping)');
        }

        $this->_totals = [];
        $this->_totals['subtotal'] = [
            'code' => 'subtotal',
            'value' => 0,
            'base_value' => 0,
            'label' => __('Subtotal')
        ];

        if ($source->getDiscountDescription()) {
            $discountLabel = __('Discount (%s)', $source->getDiscountDescription());
        } else {
            $discountLabel = __('Discount');
        }
        $this->_totals['discount'] = [
            'code' => 'discount',
            'value' => 0,
            'base_value' => 0,
            'label' => $discountLabel
        ];

        $this->_totals['tax'] = [
            'code' => 'tax',
            'value' => 0,
            'base_value' => 0,
            'label' => __('Tax')
        ];


        $this->_totals['grand_total'] = [
            'code' => 'grand_total',
            'value' => 0,
            'base_value' => 0,
            'label' => $grand_total_title
        ];

        /**
         * Base grandtotal
         */
        if ($source->isCurrencyDifferent()) {
            $this->_totals['base_grandtotal'] = [
                'code' => 'base_grandtotal',
                'value' => 0,
                'base_value' => 0,
                'label' => __('Grand Total to be Charged')
            ];
        }

        $this->_totals['paid'] = [
            'code' => 'paid',
            'value' => 0,
            'base_value' => 0,
            'label' => __('Total Paid')
        ];

        $this->_totals['refunded'] = [
            'code' => 'refunded',
            'value' => 0,
            'base_value' => 0,
            'label' => __('Total Refunded')
        ];

        $this->_totals['due'] = [
            'code' => 'due',
            'value' => 0,
            'base_value' => 0,
            'label' => __('Total Due')
        ];
        return $this;
    }

    public function getTotal($code) {
        if (isset($this->_totals[$code])) {
            return $this->_totals[$code];
        }
        return false;
    }

    public function setTotal($code, $value, $base_value = 0) {
        if (array_key_exists($code, $this->_totals)) {
            $prev_value = $this->_totals[$code]['value'];
            $this->_totals[$code]['value'] = $prev_value + $value;
            $prev_base_value = $this->_totals[$code]['base_value'];
            $this->_totals[$code]['base_value'] = $prev_value + $prev_base_value;
        }
    }

    public function getTotals() {
        //$shipping = $this->getShipping(false);
        if ($this->_totals['paid']['value'] > 0) {
            $this->_totals['due']['value'] = ($this->_totals['subtotal']['value'] + $this->_totals['tax']['value'] - $this->_totals['discount']['value']) - $this->_totals['paid']['value'];
        } else {
            $this->_totals['due']['value'] = ($this->_totals['subtotal']['value'] + $this->_totals['tax']['value']) - $this->_totals['discount']['value'];
        }

        $this->_totals['grand_total']['value'] = ($this->_totals['subtotal']['value'] + $this->_totals['tax']['value']) - $this->_totals['discount']['value'];
        if (array_key_exists('base_grandtotal', $this->_totals)) {
            $this->_totals['due']['base_value'] = ($this->_totals['subtotal']['base_value'] + $this->_totals['tax']['base_value'] + $this->_totals['shipping']['base_value']) - $this->_totals['paid']['base_value'];
            $this->_totals['base_grandtotal']['value'] = ($this->_totals['subtotal']['base_value'] + $this->_totals['tax']['base_value']) - $this->_totals['discount']['base_value'];
        }
        return $this->_totals;
    }

    public function getShipping($flag = true) {
        $shipping = [
            'code' => 'shipping',
            'value' => 'Free',
            'base_value' => 0,
            'is_formated' => true,
            'label' => __('Shipping & Handling')
        ];

        $source = $this->getOrder();

        if ($source->getShippingAmount() > 0) {

            if ($this->_objectManager->get('\Magento\Tax\Helper\Data')->displayShippingPriceIncludingTax()) {
                $_excl = $this->displayShippingPriceInclTax($source, $flag);
            } else {
                $_excl = $this->displayPriceAttribute('shipping_amount', true, ' ', $flag);
            }

            $shipping['label'] = __('Shipping & Handling (Excl. Tax)');
            $shipping['value'] = $_excl;

            $_incl = $this->displayShippingPriceInclTax($source, $flag);
            if ($this->_objectManager->get('\Magento\Tax\Helper\Data')->displayShippingBothPrices() && $_incl != $_excl) {
                $shipping['label'] = __('Shipping & Handling (Incl. Tax)');
                $shipping['value'] = $_incl;
            }
        }

        return $shipping;
    }

    public function formatValue($total) {
        if (!isset($total['is_formated']) || !$total['is_formated']) {
            return $this->getOrder()->formatPrice($total['value']);
        }
        return $total['value'];
    }

    public function canEditQty() {
        if ($this->getRequest()->getControllerName() == 'shipment') {
            return $this->getShipment();
        } elseif ($this->getRequest()->getControllerName() == 'creditmemo') {
            if ($this->getCreditmemo()->getOrder()->getPayment()->canRefund()) {
                return $this->getCreditmemo()->getOrder()->getPayment()->canRefundPartialPerInvoice();
            }
        }

        return true;
    }
    
     /**
     * Return true if can ship items partially
     *
     * @param Order|null $order
     * @return bool
     */
    public function canShipPartiallyItem($order = null)
    {
        if ($order === null || !$order instanceof \Magento\Sales\Model\Order) {
            $order = $this->_coreRegistry->registry('current_shipment')->getOrder();
        }
        $value = $order->getCanShipPartiallyItem();
        if ($value !== null && !$value) {
            return false;
        }
        return true;
    }
    
    /**
     * Return true if can ship partially
     *
     * @param Order|null $order
     * @return bool
     */
    public function canShipPartially($order = null)
    {
        if ($order === null || !$order instanceof Order) {
            $order = $this->_coreRegistry->registry('current_shipment')->getOrder();
        }
        $value = $order->getCanShipPartially();
        if ($value !== null && !$value) {
            return false;
        }
        return true;
    }
    
    /**
     * Whether to show 'Return to stock' column for item parent
     *
     * @param Item $item
     * @return bool
     */
    public function canParentReturnToStock($item = null)
    {
        if ($item !== null) {
            if ($item->getCreditmemo()->getOrder()->hasCanReturnToStock()) {
                return $item->getCreditmemo()->getOrder()->getCanReturnToStock();
            }
        } elseif ($this->getOrder()->hasCanReturnToStock()) {
            return $this->getOrder()->getCanReturnToStock();
        }
        return $this->canReturnToStock();
    }
    
    /**
     * Whether to show 'Return to stock' checkbox for item
     *
     * @param Item $item
     * @return bool
     */
    public function canReturnItemToStock($item = null)
    {
        if (null !== $item) {
            if (!$item->hasCanReturnToStock()) {
                $stockItem = $this->stockRegistry->getStockItem(
                    $item->getOrderItem()->getProductId(),
                    $item->getOrderItem()->getStore()->getWebsiteId()
                );
                $item->setCanReturnToStock($stockItem->getManageStock());
            }
            return $item->getCanReturnToStock();
        }

        return $this->canReturnToStock();
    }
    
    /**
     * Add column renderers
     *
     * @param array $blocks
     * @return $this
     */
    public function setColumnRenders(array $blocks)
    {
        foreach ($blocks as $blockName) {
            $block = $this->getLayout()->getBlock($blockName);
            if ($block->getRenderedBlock() === null) {
                $block->setRenderedBlock($this);
            }
            $this->_columnRenders[$blockName] = $block;
        }
        return $this;
    }
    
    /**
     * Retrieve column renderer block
     *
     * @param string $column
     * @param string $compositePart
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function getColumnRenderer($column, $compositePart = '')
    {
        $column = 'column_' . $column;
        if (isset($this->_columnRenders[$column . '_' . $compositePart])) {
            $column .= '_' . $compositePart;
        }
        if (!isset($this->_columnRenders[$column])) {
            return false;
        }
        return $this->_columnRenders[$column];
    }
    
    /**
     * Retrieve rendered column html content
     *
     * @param \Magento\Framework\DataObject $item
     * @param string $column the column key
     * @param string $field the custom item field
     * @return string
     */
    public function getColumnHtml(\Magento\Framework\DataObject $item, $column, $field = null)
    {
        if ($item->getOrderItem()) {
            $block = $this->getColumnRenderer($column, $item->getOrderItem()->getProductType());
        } else {
            $block = $this->getColumnRenderer($column, $item->getProductType());
        }

        if ($block) {
            $block->setItem($item);
            if ($field !== null) {
                $block->setField($field);
            }
            return $block->toHtml();
        }
        return '&nbsp;';
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return bool
     */
    public function canReturnToStock($store = null)
    {
        return $this->_objectManager->get("\Magento\CatalogInventory\Api\StockConfigurationInterface")->canSubtractQty($store);
    }

    public function getCarriers() {
        $carriers = [];
        $carrierInstances = $this->_objectManager->get('\Magento\Shipping\Model\Config')->getAllCarriers(
                $this->getShipment()->getStoreId()
        );
        $carriers['custom'] = __('Custom Value');
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $carriers[$code] = $carrier->getConfigData('title');
            }
        }
        return $carriers;
    }

    public function displayShippingPriceInclTax($order, $flag = true) {
        $shipping = $order->getShippingInclTax();
        if ($shipping) {
            $baseShipping = $order->getBaseShippingInclTax();
        } else {
            $shipping = $order->getShippingAmount() + $order->getShippingTaxAmount();
            $baseShipping = $order->getBaseShippingAmount() + $order->getBaseShippingTaxAmount();
        }
        if ($flag == false) {
            return $baseShipping;
        } else {
            return $this->displayPrices($this->getPriceDataObject(), $baseShipping, $shipping, false, ' ');
        }
    }

    /**
     * Retrieve price attribute html content
     *
     * @param string $code
     * @param bool $strong
     * @param string $separator
     * @return boolean $flag
     */
    public function displayPriceAttribute($code, $strong = false, $separator = '<br/>', $flag = true) {
        $obj = $this->getPriceDataObject();
        if ($flag == false) {
            return $obj->getData('base_' . $code);
        }

        return $this->displayPrices(
                        $obj, $obj->getData('base_' . $code), $obj->getData($code), $strong, $separator
        );
    }


    /**
     * Retrieve price data object
     *
     * @return Order
     */
    public function getPriceDataObject()
    {
        $obj = $this->getData('price_data_object');
        if ($obj === null) {
            return $this->getOrder();
        }
        return $obj;
    }

    
    /**
     * Retrieve price formatted html content
     *
     * @param \Magento\Framework\DataObject $dataObject
     * @param float $basePrice
     * @param float $price
     * @param bool $strong
     * @param string $separator
     * @return string
     */
    public function displayPrices($dataObject, $basePrice, $price, $strong = false, $separator = '<br/>') {
        $order = false;
        if ($dataObject instanceof \Magento\Sales\Model\Order) {
            $order = $dataObject;
        } else {
            $order = $dataObject->getOrder();
        }

        if ($order && $order->isCurrencyDifferent()) {
//            $res = $order->getBaseCurrency()->formatPrecision($basePrice, 2, array(), false);
//            $res.= $separator . '[' . $order->getOrderCurrency()->formatPrecision($price, 2, array(), false) . ']';
            $res = '';
            $res .= $order->formatBasePricePrecision($basePrice, 2);
            $res .= $separator;
            $res .= $order->formatPricePrecision($price, 2, true);
        } elseif ($order) {
            $res = $order->formatPricePrecision($price, 2);
        } else {
            $res = $this->_storeManager->getStore()->formatPrice($price, false);
            if ($strong) {
                $res = '<strong>' . $res . '</strong>';
            }
        }
        return $res;
    }
    
    public function canPrintOrder(){
        return $this->_coreRegistry->registry("vssmp_print_order");
    }
    
    /**
     * Returns string with formatted address
     *
     * @param Address $address
     * @return null|string
     */
    public function getFormattedAddress(Address $address)
    {
        return $this->addressRenderer->format($address, 'html');
    }

    
    /**
     * Check is shipment is regular
     *
     * @return bool
     */
    public function isShipmentRegular()
    {
        if (!$this->canShipPartiallyItem() || !$this->canShipPartially()) {
            return false;
        }
        return true;
    }
}
