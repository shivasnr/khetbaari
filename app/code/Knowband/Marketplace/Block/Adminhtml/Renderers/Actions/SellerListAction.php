<?php

namespace Knowband\Marketplace\Block\Adminhtml\Renderers\Actions;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;

class SellerListAction extends AbstractRenderer {

    /**
     * @var UrlInterface
     */
    protected $_frameworkUrlInterface;

    public function render(DataObject $row) {
        $data = $row->getData();
        $html = '';
        $html .= '<button type="button" class="scalable back" onclick="redirectLocation(\'' . $this->getUrl('mpadmin/marketplace/orderList', ['id' => $data['entity_id']]) . '\')">' . __('Orders') . '</button>';
        $html .= '<button type="button" class="scalable back" onclick="redirectLocation(\'' . $this->getUrl('mpadmin/marketplace/sellerProductList', ['id' => $data['entity_id']]) . '\')">' . __('Products') . '</button>';

        return $html;
    }

}