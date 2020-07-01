<?php 

namespace Hariyo\SocialLogin\Observer;

use Magento\Framework\Event\ObserverInterface;

class TestObserver implements ObserverInterface
{

  public function execute(\Magento\Framework\Event\Observer $observer)
  {
        $displayText = $observer->getData('test_text');
        echo $displayText->getText() . " - Event "."\n";
		    $displayText->setText('Execute event successfully.');

		return $this;
  }
}
