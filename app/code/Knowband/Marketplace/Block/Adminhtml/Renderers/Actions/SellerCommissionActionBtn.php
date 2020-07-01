<?php

namespace Knowband\Marketplace\Block\Adminhtml\Renderers\Actions;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;

class SellerCommissionActionBtn extends AbstractRenderer {

    /**
     * @var UrlInterface
     */
    protected $_frameworkUrlInterface;

    public function render(DataObject $row) {
        $data = $row->getData();
        $html = '';
        $redirectUrl = $this->getUrl('mpadmin/marketplace/transactionHistory', ['seller_id' => $data['seller_id']]);
        $html .= '<button type="button" class="scalable back" onclick="redirectLocation(\''.$redirectUrl.'\')">' . __('View') . '</button>';

        return $html;
    }

}