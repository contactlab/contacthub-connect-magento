<?php 
class Contactlab_Hub_Model_Customer extends Mage_Customer_Model_Customer
{
    /**
     * Send email with new account related information
     *
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @throws Mage_Core_Exception
     * @return Mage_Customer_Model_Customer
     */
    public function sendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0')
    {
     
        if(
            (Mage::helper('contactlab_hub')->isDiabledSendingNewCustomerEmail($storeId))
            &&  ($type == 'registered' || $type == 'confirmed')
            )        
        {
            return parent;
        }
        
        return parent::sendNewAccountEmail($type, $backUrl, $storeId);
        
    }
}