<?php
namespace Hariyo\SocialLogin\Controller\Index;

use Magento\Framework\App\Action\Context;
use Hariyo\SocialLogin\Service\Google;

class Auth extends \Magento\Framework\App\Action\Action
{

   protected $googleService;

   public function __construct(
       Context $context,
       Google $googleService
    ){
    
       parent::__construct($context);
       $this->googleService = $googleService;
   }
    
    public function execute()
    {
        
        echo  json_encode($this->googleService->request(''));
        exit;

    }

}