<?php

namespace Knowband\Marketplace\Block\Adminhtml\Renderers\Actions;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;

class OrderActionBtn extends AbstractRenderer {

    /**
     * @var UrlInterface
     */
    protected $_frameworkUrlInterface;

    public function render(DataObject $row) {
        $data = $row->getData();
        $html = '';
        $edit_url = $this->getUrl('sales/order/view', ['order_id' => $data['order_id']]);
        $html .= "<button type='button' onclick='location.href=".'"'.$edit_url.'"'."' class='scalable back' style='margin-right:5px;'>".__('Edit')."</button>";

        return $html;
    }

}