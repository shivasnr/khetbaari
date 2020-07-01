<?php

namespace Knowband\Marketplace\Model;

class Categorymapping extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_track_category_mapping';
    protected $_cacheTag = 'vss_mp_track_category_mapping';
    protected $_eventPrefix = 'vss_mp_track_category_mapping';

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Categorymapping');
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
