<?php

namespace Knowband\Marketplace\Model;

class Transactions extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_seller_transactions';
    protected $_cacheTag = 'vss_mp_seller_transactions';
    protected $_eventPrefix = 'vss_mp_seller_transactions';
    
    CONST CREDIT = 1;
    CONST DEBIT = 2;

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Transactions');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }
    
    public function getTransactionTypes() {
        return [
            self::CREDIT => __('Credit'),
            self::DEBIT => __('Debit')
        ];
    }

    public function getDefaultType() {
        return self::CREDIT;
    }

    public function getTypeLabel($code) {
        if ($code == self::CREDIT) {
            return __('Credit');
        } else if ($code == self::DEBIT) {
            return __('Debit');
        } else {
            return __('Unknown');
        }
    }

}
