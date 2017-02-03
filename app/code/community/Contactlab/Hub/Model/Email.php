<?php
/**
 * This class wraps the Basic email sending functionality
 * If SMTPPro is enabled it will send emails using the given
 * configuration.
 *
 * @author Ashley Schroder (aschroder.com)
 * @copyright  Copyright (c) 2014 Ashley Schroder
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
