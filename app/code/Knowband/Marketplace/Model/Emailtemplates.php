<?php

namespace Knowband\Marketplace\Model;

class Emailtemplates extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_email_templates';
    protected $_cacheTag = 'vss_mp_email_templates';
    protected $_eventPrefix = 'vss_mp_email_templates';

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Emailtemplates');
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
