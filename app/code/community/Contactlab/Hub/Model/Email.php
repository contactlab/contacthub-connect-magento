<?php
class Contactlab_Hub_Model_Email extends Mage_Core_Model_Email 
{

	protected $_helper;
	
    public function send() 
    {
		// If it's not enabled, just return the parent result.
        if (!$this->isEnabled()) {
            return parent::send();
        }

        if (Mage::getStoreConfigFlag('system/smtp/disable')) {
            return $this;
        }

        $mail = new Zend_Mail();

        if (strtolower($this->getType()) == 'html') {
            $mail->setBodyHtml($this->getBody());
        } else {
            $mail->setBodyText($this->getBody());
        }

        $mail->setFrom($this->getFromEmail(), $this->getFromName())
            ->addTo($this->getToEmail(), $this->getToName())
            ->setSubject($this->getSubject());

        $transport = Mage::getModel('contactlab_hub/email_transport_hub');
        
        if ($transport->getTransport()) { // if set by an observer, use it
            $mail->send($transport->getTransport());
        } else {
            $mail->send();
        }

        return $this;
    }
    
    protected function _helper()
    {
    	if (!$this->_helper)
    	{
    		$this->_helper = Mage::helper('contactlab_hub');
    	}
    	return $this->_helper;
    }
    
    protected function _isEnabled()
    {
    	return (bool)$this->_helper()->getConfigData('email/enabled');
    }
}
