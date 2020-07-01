<?php

namespace Knowband\Marketplace\Model;

class Statusaction extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_statusaction';
    protected $_cacheTag = 'vss_mp_statusaction';
    protected $_eventPrefix = 'vss_mp_statusaction';

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Statusaction');
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
