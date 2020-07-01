<?php

namespace Knowband\Marketplace\Block\Adminhtml\Renderers;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Blank extends AbstractRenderer {

    protected $_row = null;

    public function render(DataObject $row) {
        $this->_row = $row;
        $value = $row->getData($this->getColumn()->getIndex());
        $type = trim($this->getColumn()->getData('type'));
        if (empty($value)) {
            if ($type == 'price' || $type == 'currency') {
                $value = 0;
            } else {
                $value = 'NA';
            }
        } else {
            if ($type != 'price' && $type != 'currency') {
                return parent::render($row);
            }
        }
        if ($type == 'price' || $type == 'currency') {
            $value = $this->renderCurrencyValue($value);
        }

        return $value;
    }

    private function renderCurrencyValue($value) {
        $currency_code = $this->_getCurrencyCode();

        if (!$currency_code) {
            return $value;
        }

        $data = floatval($value) * $this->_getRate();
        $sign = (bool) (int) $this->getColumn()->getShowNumberSign() && ($data > 0) ? '+' : '';
        $data = sprintf("%f", $data);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $localCurrency = $objectManager->get("\Magento\Framework\Locale\CurrencyInterface");
        $data = $localCurrency->getCurrency($currency_code)->toCurrency($data);
        return $sign . $data;
    }

    /**
     * Returns currency code, false on error
     *
     * @param $row
     * @return string|false
     */
    protected function _getCurrencyCode() {
        if ($code = $this->getColumn()->getCurrencyCode()) {
            return $code;
        }

        if ($code = $this->_row->getData($this->getColumn()->getCurrency())) {
            return $code;
        }
        return false;
    }

    /**
     * Get rate for current row, 1 by default
     *
     * @param $row
     * @return float|int
     */
    protected function _getRate() {
        if ($rate = $this->getColumn()->getRate()) {
            return floatval($rate);
        }
        if ($rate = $this->_row->getData($this->getColumn()->getRateField())) {
            return floatval($rate);
        }
        return 1;
    }

}
