<?php
/**
* 
*/
class Contactlab_Hub_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_saveLog = false;
    protected $_logFilename = false;

    public function __construct() 
    {
        $this->_saveLog = $this->getConfigData('settings/log');
        $this->_logFilename = $this->getConfigData('settings/logfilename')?:'contactlabhub.log';
    }

    public function getConfigData($key, $storeId=null)
    {
		if(!$storeId)
		{
			$storeId = $this->getStore()->getStoreId();
		}
		return $this->getConfigStoredData($key, $storeId);
	}

	public function getConfigStoredData($key, $storeId=null) 
	{
		return Mage::getStoreConfig('contactlab_hub/'.$key, $storeId);
	}

	public function getConfigDefaultData($key) 
	{
		return Mage::getConfig()->getNode('default/contactlab_hub/'.$key);
	}

	public function setConfigData($path, $value, $scope='default', $scopeId=0) 
	{		
		Mage::getConfig()
            ->saveConfig($path, $value, $scope, $scopeId)
            ->reinit();
	
        Mage::app()->reinitStores();
	}

	public function getStore()
	{
		return Mage::app()->getStore();
	}
	
    /**
     * @param $message
     * @param null $level
     * @return bool
     *
     * @codeCoverageIgnore
     */
	public function log($message, $level = null) 
	{
		if (!$this->_saveLog && $level == null) 
		{
			return false;
		}
		Mage::log($message, $level, $this->_logFilename);
	}
	
	public function getJsConfigData()
	{
		$config->workspaceId = $this->getConfigData('settings/apiworkspaceid');
		$config->nodeId = $this->getConfigData('settings/apinodeid');
		$config->token = $this->getConfigData('settings/apitoken');
		$config->context = 'ECOMMERCE';		
		$config->contextInfo->store->id = "".Mage::app()->getStore()->getStoreId();
		$config->contextInfo->store->name = Mage::app()->getStore()->getName();
		$config->contextInfo->store->country = Mage::getStoreConfig('general/country/default');
		$config->contextInfo->store->website = Mage::getUrl('', array('_store' => Mage::app()->getStore()->getStoreId()));
		$config->contextInfo->store->type = "ECOMMERCE";
		return "\nch('config', ".json_encode($config).");";
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
		if(!$customer = $this->getCustomer())
		{
			return null;
		}
		$customerInfo->base->firstName = $customer->getFirstname();
		$customerInfo->base->lastName = $customer->getLastname();
		$customerInfo->base->contacts->email = $customer->getEmail();
		return "\nch('customer',".json_encode($customerInfo).");";
	}
	
	public function getCategoryPageTracking()
	{
		$category = Mage::registry('current_category');
		$tracking = "";			
		$evtName = 'events/viewedProductCategory';
		if ($this->getConfigData($evtName)) 
		{			
			$searchQuery = Mage::app()->getRequest()->getParam('q');	
			$currentLayer = Mage::registry('current_layer');
			$searchResult = ($currentLayer instanceof Varien_Object)?$currentLayer->getProductCollection()->getAllIds():array();
			$tracking.= "";				
			$tracking.= $this->_getJsCoustomerInfo();
			$categoryJs->type = 'viewedProductCategory';
			$categoryJs->additionalProperties = false;
			$categoryJs->properties->category = $this->clearStrings($category->getName());
			$tracking.= "\nch('event',".json_encode($categoryJs).");";			
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
		$evtName = 'events/viewedProduct';
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
			$tracking.= $this->_getJsCoustomerInfo();				
			$productJs->type = 'viewedProduct';
			$productJs->properties->id = $product->getEntityId();
			$productJs->properties->sku = $product->getSku();
			$productJs->properties->name = $this->clearStrings($product->getName());
			$productJs->properties->price = round($product->getFinalPrice(),2);
			$productJs->properties->imageUrl = $product->getEntityId();
			$productJs->properties->linkUrl = $product->getProductUrl();
			$productJs->properties->shortDescription = $this->clearStrings($product->getShortDescription());
			$productJs->properties->category = $categories;
			$tracking.= "\nch('event',".json_encode($productJs).");";
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
		$evtName = 'events/searched';
		if ($this->getConfigData($evtName)) 
		{			
			$searchQuery = Mage::app()->getRequest()->getParam('q');	
			$currentLayer = Mage::registry('current_layer');
			$searchResult = ($currentLayer instanceof Varien_Object) ? count($currentLayer->getProductCollection()->getAllIds()) : 0;
			$tracking.= "";
			$tracking.= $this->_getJsCoustomerInfo();
			$searchJs->type = 'searched';
			$searchJs->properties->keyword = $this->clearStrings($searchQuery);
			$searchJs->properties->resultCount = $searchResult;
			$tracking.= "\nch('event',".json_encode($searchJs).");";			
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
		//return json_encode(str_replace(PHP_EOL, ' ', strip_tags(trim($string))));
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