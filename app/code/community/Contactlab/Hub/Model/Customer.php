<?php 
class Contactlab_Hub_Model_Customer extends Mage_Customer_Model_Customer
{
    /**
     * {@inheritdoc}
     */
    public function sendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0', $password = null)
    {
        if(Mage::helper('contactlab_hub')->isDiabledSendingNewCustomerEmail($storeId)
            && ($type == 'registered' || $type == 'confirmed')
        ) {
            return parent;
        }
        
        return parent::sendNewAccountEmail($type, $backUrl, $storeId, $password);
        
    }
}