<?php

namespace Knowband\Marketplace\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;

class Cron extends \Magento\Backend\Block\Template {
    
    public function __construct(
            Context $context
            )
    {
        parent::__construct($context);
    }
    
    public function getFrontEndUrl($action) {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $frontendUrlBuilder = $om->get(\Magento\Framework\Url::class);
        $url = $frontendUrlBuilder->getUrl(
            $action,
            [
                '_secure' => true,
            ]
        );
        return $url;
    }
}


