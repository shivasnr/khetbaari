<?php

namespace Knowband\Marketplace\Model;

class Reason extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_reasons';
    protected $_cacheTag = 'vss_mp_reasons';
    protected $_eventPrefix = 'vss_mp_reasons';
    const REASON_MIN_LENGTH = 30;

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Reason');
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
}
