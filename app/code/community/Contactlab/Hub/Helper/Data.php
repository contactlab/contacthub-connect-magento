<?php
/**
* 
*/
class Contactlab_Hub_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_saveLog = false;
    protected $_logFilename = false;

    public function __construct() {
        $this->_saveLog = $this->getConfigData('settings/log');
        $this->_logFilename = $this->getConfigData('settings/logfilename')?:'contactlabhub.log';
    }

    public function getConfigData($key) {
		/*$result = $this->getConfigStoredData($key);
		if ($result == null) {
			$result = $this->getConfigDefaultData($key);
		}*/
		return $this->getConfigStoredData($key);
	}

	public function getConfigStoredData($key) {
		return Mage::getStoreConfig('contactlab_hub/'.$key);
	}

	public function getConfigDefaultData($key) {
		return Mage::getConfig()->getNode('default/contactlab_hub/'.$key);
	}

	public function setConfigData($key, $value) {
		Mage::getConfig()
            ->saveConfig('contactlab_hub/'.$key, $value, 'default', 0)
            ->reinit();
        Mage::app()->reinitStores();
	}

    /**
     * @param $message
     * @param null $level
     * @return bool
     *
     * @codeCoverageIgnore
     */
	public function log($message, $level = null) {
		if (!$this->_saveLog && $level == null) {
			return false;
		}
		Mage::log($message, $level, $this->_logFilename);
	}
	
	public function getJsConfigData()
	{		
		$config = array(
					"workspaceId" 	=> $this->getConfigData('settings/apiworkspaceid'),
					"nodeId"		=> $this->getConfigData('settings/apinodeid'),
					"token"			=> $this->getConfigData('settings/apitoken'),
					"context"		=> 'ECOMMERCE'					
				);
		return json_encode($config);
	}
	
	public function getCustomer()
	{
		$customer = null;
		if(Mage::getSingleton('customer/session')->isLoggedIn())
		{
			$customer = Mage::getModel('customer/customer')->load(Mage::getSingleton('customer/session')->getCustomer()->getId());
		}
		return $customer;
	}
	
	protected function _getJsCoustomerInfo()
	{
		$customer = $this->getCustomer();
		$customerInfo = array(
					"externalId" 	=> $customer->getEntityId(),				
					"base"	=> array( 							
								"firstName"	=> $customer->getFirstname(),
								"lastName"	=> $customer->getLastname(),
								"contacts"	=>	array(
											"email"	=> $customer->getEmail()
								)														
							
					)			
					
		);
		
		return json_encode($customerInfo);
	}
	
	public function getCategoryPageTracking()
	{
		$category = Mage::registry('current_category');
		$tracking = "";			
		$evtName = 'events/evtcategoryview';
		if ($this->getConfigData($evtName)) 
		{			
			$searchQuery = Mage::app()->getRequest()->getParam('q');	
			$currentLayer = Mage::registry('current_layer');
			$searchResult = ($currentLayer instanceof Varien_Object)?$currentLayer->getProductCollection()->getAllIds():[];
			$tracking.= "";				
			if($this->getCustomer())
			{
				$tracking.= " 
					ch('customer',".$this->_getJsCoustomerInfo().");";
			}
			$tracking.= " 									
					ch('event', {
	            			type: 'viewedProductCategory',	            			
	            			additionalProperties:false,
	            			properties:{
	            				category:'".$this->clearStrings($category->getName())."',	            					            					            			
							}
	        		});				
			";
		}
		else 
		{
			$this->log($evtName.' OFF');
		}
		return $tracking;
	}
	
	public function getProductPageTracking()
	{
		$tracking = "";			
		$evtName = 'events/evtview';
		if ($this->getConfigData($evtName)) 
		{
			$product = Mage::registry('current_product');				
			$categories = array();
			foreach($product->getCategoryIds() as $categoryId)
			{
				$category = Mage::getModel('catalog/category')->load($categoryId);
				$categories[] = $category->getName();
			}
			$tracking.= "";
			if($this->getCustomer())
			{
				$tracking.= " 
					ch('customer',".$this->_getJsCoustomerInfo().");";
			}
			$tracking.= " 
					ch('event', {
	            			type: 'viewedProduct',
	            			properties: {
								id: '".$product->getEntityId()."',
								sku: '".$product->getSku()."',
								name: '".$this->clearStrings($product->getName())."',
								price: ".round($product->getFinalPrice(),2).",
								imageUrl: '".$product->getImageUrl()."',
								linkUrl: '".$product->getProductUrl()."',
								shortDescription:'".$this->clearStrings($product->getShortDescription())."',
								category: ".json_encode($categories).",                
							}
	        		}); 									
			";
		}
		else 
		{
			$this->log($evtName.' OFF');
		}
		return $tracking;
	}
	
	public function getSearchTracking()
	{		
		$tracking = "";			
		$evtName = 'events/evtsearch';
		if ($this->getConfigData($evtName)) 
		{			
			$searchQuery = Mage::app()->getRequest()->getParam('q');	
			$currentLayer = Mage::registry('current_layer');
			$searchResult = ($currentLayer instanceof Varien_Object) ? count($currentLayer->getProductCollection()->getAllIds()) : 0;
			$tracking.= "";
			if($this->getCustomer())
			{
				$tracking.= " 
					ch('customer',".$this->_getJsCoustomerInfo().");";
			}
			$tracking.= " 					
					ch('event', {
	            			type: 'searched',	            				            			
	            			properties:{
	            				keyword:'".$this->clearStrings($searchQuery)."',
	            				resultCount:".$searchResult."	            					            			 
							}
	        		}); 					
			";
		}
		else 
		{
			$this->log($evtName.' OFF');
		}
		return $tracking;
	}
	
	public function clearStrings($string) 
	{
		return trim(str_replace("''", "", str_replace("\n", " ",strip_tags($string))));
	}
	
	public function getUserAgent()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	}
	
	public function getRemoteIpAddress()
	{
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
	}
	
	public function sendAnonymousEvent() 
	{
		return (bool)$this->getConfigData('settings/send_anonymous');
	}
}