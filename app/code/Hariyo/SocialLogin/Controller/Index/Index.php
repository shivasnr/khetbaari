<?php

namespace Hariyo\SocialLogin\Controller\Index;

use Hariyo\SocialLogin\Controller\Index as ControllerIndex;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Hariyo\SocialLogin\Service\Google;
use Magento\Framework\Event\ManagerInterface as EventManager;

class Index extends \Magento\Framework\App\Action\Action
{

   protected $googleService;
   protected $resultJsonFactory;
   protected $eventManager;

   public function __construct(
       Context $context,
       \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
       EventManager $eventManager
    ){
       $this->resultJsonFactory = $resultJsonFactory;
       $this->eventManager = $eventManager;
       parent::__construct($context);
       //$this->googleService = $googleService;
   }
    
    public function execute()
    {
        $textDisplay = new \Magento\Framework\DataObject(array('text' => 'Hariyo ban nepalko dhan'));
		$this->eventManager->dispatch('hariyo_event_before', ['test_text' => $textDisplay]);
		// echo $textDisplay->getText();
		// exit;
    
        $result = $this->resultJsonFactory->create();
        $data = ['message' => $textDisplay->getText()];

        return $result->setData($data);

    }

    
}