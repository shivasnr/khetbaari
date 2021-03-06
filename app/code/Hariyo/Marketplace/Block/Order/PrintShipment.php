<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hariyo\Marketplace\Block\Order;

/**
 * Abstract block for display sales (quote/order/invoice etc.) items
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class PrintShipment extends \Hariyo\Marketplace\Block\Order\Items\AbstractItems
{
   /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    protected $addressRenderer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
    ) {
        $this->_paymentHelper = $paymentHelper;
        $this->_coreRegistry = $registry;
        $this->addressRenderer = $addressRenderer;
        parent::__construct($context);
    }
    
     /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Print Order # %1', $this->getOrder()->getRealOrderId()));
        $infoBlock = $this->_paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        $this->setChild('payment_info', $infoBlock);
    }

    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

    /**
     * @return array|null
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * @param AbstractBlock $renderer
     * @return $this
     */
    protected function _prepareItem(\Magento\Framework\View\Element\AbstractBlock $renderer)
    {
        $renderer->setPrintStatus(true);
        return parent::_prepareItem($renderer);
    }

    /**
     * Returns string with formatted address
     *
     * @param Address $address
     * @return null|string
     */
    public function getFormattedAddress(\Magento\Sales\Model\Order\Address $address)
    {
        return $this->addressRenderer->format($address, 'html');
    }
}
