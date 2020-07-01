<?php

namespace Knowband\Marketplace\Model;

class Settings extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_seller_settings';
    protected $_cacheTag = 'vss_mp_seller_settings';
    protected $_eventPrefix = 'vss_mp_seller_settings';

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Settings');
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
