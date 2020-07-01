<?php
namespace Hariyo\SocialLogin\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NotFoundException;

abstract class Index extends \Magento\Framework\App\Action\Action
{

     /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        echo 'hell yeah';exit;
        // if (!$this->contactsConfig->isEnabled()) {
        //     throw new NotFoundException(__('Page not found.'));
        // }
        return parent::dispatch($request);
    }

}