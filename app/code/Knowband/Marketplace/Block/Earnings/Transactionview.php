<?php

namespace Knowband\Marketplace\Block\Earnings;

class Transactionview extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Knowband\Marketplace\Model\Transactions $mpTransactionModel,
            \Knowband\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->mp_dataHelper = $mpDataHelper;
        $this->mp_transactionModel = $mpTransactionModel;
        $this->_timezone = $timezone;
        parent::__construct($context);
    }
    
    public function getTransactionData() {
        $transaction_row_id = $this->getRequest()->getParam('id');

        $collection = $this->mp_transactionModel->load($transaction_row_id);
        $data = $collection->getData();
        $collection->unsetData();
        if (!empty($data)) {
            $data['found'] = true;
            if ($data['type'] == \Knowband\Marketplace\Model\Transactions::DEBIT) {
                $data['transfer_info'] = sprintf(__('Amount of %s debited from your earning on %s'), $this->mp_dataHelper->formatCurrency($data['amount']), $this->_timezone->formatDate($data['created_at']));
            } else {
                $data['transfer_info'] = sprintf(__('Amount of %s credited into your account on %s'), $this->mp_dataHelper->formatCurrency($data['amount']), $this->_timezone->formatDate($data['created_at']));
            }
        } else {
            $data['found'] = false;
        }
        return $data;
    }

}
