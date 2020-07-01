<?php

namespace Knowband\Marketplace\Model;

class Product extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_product_to_seller';
    protected $_cacheTag = 'vss_mp_product_to_seller';
    protected $_eventPrefix = 'vss_mp_product_to_seller';

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Product');
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
