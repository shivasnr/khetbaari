<?php

namespace Knowband\Marketplace\Model\Mail;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
   public function addAttachment($attachment)
    {
       if (is_array($attachment) && $attachment != null) {
            $this->message->createAttachment(
                    $attachment['content'], \Zend_Mime::TYPE_OCTETSTREAM, \Zend_Mime::DISPOSITION_ATTACHMENT, \Zend_Mime::ENCODING_BASE64, $attachment['name']
            );
        }
        return $this;
    }
    
    public function clearFrom()
    {
        //$this->_from = null;
        $this->message->clearFrom('From');
        return $this;
    }
 
    public function clearSubject()
    {
        $this->message->clearSubject();
        return $this;
    }
 
    public function clearMessageId()
    {
        $this->message->clearMessageId();
        return $this;
    }
 
    public function clearBody()
    {
        $this->message->setParts([]);
        return $this;
    }
 
    public function clearRecipients()
    {
        $this->message->clearRecipients();
        return $this;
    }
    
    public function clearHeader($headerName)
    {
        if (isset($this->_headers[$headerName])){
            unset($this->_headers[$headerName]);
        }
        return $this;
    }
}
