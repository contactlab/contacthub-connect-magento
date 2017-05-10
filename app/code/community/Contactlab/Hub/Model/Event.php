<?php
class Contactlab_Hub_Model_Event extends Mage_Core_Model_Abstract
{
	
	const CONTACTLAB_HUB_STATUS_TO_EXPORT	= 0;
    const CONTACTLAB_HUB_STATUS_EXPORTED	= 1;
    const CONTACTLAB_HUB_STATUS_PROCESSING	= 2;
    const CONTACTLAB_HUB_STATUS_FAILED	    = 3;

    protected $_helper;
	protected $_hub;
	protected $_unexportedEvents;
	protected $_remoteCustomerHubId;
	protected $_eventForHub;
    protected $_ciao; 
    
    protected function _construct()
    {
        $this->_init('contactlab_hub/event');
    }

    protected function _helper()
    {
        if (!$this->_helper) 
        {
            $this->_helper = Mage::helper('contactlab_hub');
        }
        return $this->_helper;
    }
    
    public function getUnexportedEvents($pageSize = null)
    {
    	if(!$this->_unexportedEvents)
    	{
    		$collection = $this->getCollection()
    					->addFieldToSelect('entity_id')
    					->addFieldToSelect('model')
    					->addFieldToFilter('status', array('eq' => self::CONTACTLAB_HUB_STATUS_TO_EXPORT));
    		if($pageSize)
    		{
    			$collection->getSelect()->limit($pageSize);    			
    		}
    		$unexportedEvents = array();
    		foreach ($collection as $event)
    		{    			    			    			    			    			
    			$unexportedEvents[] = Mage::getModel('contactlab_hub/event_'.$event->getModel())->load($event->getEntityId());    			    			
    		}
    		$this->_unexportedEvents = $unexportedEvents;
    	}
    	return $this->_unexportedEvents;
    }
   
    public function trace()
    {
    	$this->_helper()->log(__METHOD__);
    	$this->_assignData();    	
    	$websiteId = Mage::getModel('core/store')->load($this->getStoreId())->getWebsiteId();
    	$customer = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->loadByEmail($this->getIdentityEmail());
    	if ($this->_helper()->getConfigData('events/'.$this->getName(), $this->getStoreId()))
    	{    		    		    	    		
	    	if($customer || $this->_helper()->sendAnonymousEvent())
	    	{    
	    		$this->save();		    	
	    	}
	    	else
	    	{
	    		$this->_helper()->log('events/send anonymous events OFF');
	    	}
    	}
    	else 
    	{    		
    		$this->_helper()->log($this->getEntityId().' disbled');
    	}
    	return $this;
    }
    
    
    public function export()
    {
    	$this->_helper()->log(__METHOD__);	
    	try
    	{  
    		$this->setStatus(self::CONTACTLAB_HUB_STATUS_PROCESSING);    	    	
	    	$this->_createUpdateCustomer();
    		$hubEvent = $this->_composeHubEvent();     		
    		$this->_getHub()->createEvent($hubEvent);
    		$this->setHubEvent(json_encode($hubEvent));
    		$this->setExportedDate(date('Y-m-d H:i:s'));
    		$this->setStatus(self::CONTACTLAB_HUB_STATUS_EXPORTED);    	    		
    		$this->save();    		    	
    	}    	
    	catch (exception $e)
    	{    		
    		$this->setStatus(self::CONTACTLAB_HUB_STATUS_FAILED);
    		$this->save();    		    		
    		$this->_helper()->log($e->getMessage());
    	}  
    	$this->_helper()->log('fine export event');
    	return $this;
    }
    
    protected function _getSid()
    {
    	if(Mage::getModel('core/cookie')->get('_ch'))
    	{
	    	$cookie = json_decode(Mage::getModel('core/cookie')->get('_ch'));
	    	if($cookie->sid)
	    	{
	    		$this->setSessionId($cookie->sid);
	    	}
    	}
    	else
    	{
    		$this->_helper()->log('Cookie disabled');    		
    	}
    	return $this->getSessionId();
    }
    
    protected function _assignData()
    {    	    	    	
    	
    	$this->setCreatedAt(date('Y-m-d H:i:s'))
    		->setEnvUserAgent($this->_helper()->getUserAgent());    		  	
    		
    	if(!$this->getEnvRemoteIp())
    	{
    		$this->setEnvRemoteIp($this->_helper()->getRemoteIpAddress());
    	}		
    	if(!$this->getNeedUpdateIdentity())
    	{
    		$this->setNeedUpdateIdentity(false);
    	}
    	if(!$this->getStoreId())
    	{
    		$this->setStoreId(Mage::app()->getStore()->getStoreId());
    	}    		
    	if($customer = $this->_helper()->getCustomer())
    	{
    		$this->setIdentityEmail($customer->getEmail());
    	}
    	return $this;
    }
    
    
    protected function _getHub()
    {
    	if(!$this->_hub)
    	{
    		$this->_hub = Mage::getModel('contactlab_hub/hub')->setStoreId($this->getStoreId());
    	}
    	return $this->_hub;
    }

    
    protected function _createUpdateCustomer()
    {
    	$this->_helper()->log(__METHOD__);
    	
    	$this->_remoteCustomerHubId = null;
    	if($this->getNeedUpdateIdentity())
    	{    		
    		$customerData = $this->_getCustomerDataForHub();
    		$remoteCustomerHubId = $this->_getRemoteCustomerHub($customerData);
    		if($remoteCustomerHubId)
    		{
    			$this->_remoteCustomerHubId = $remoteCustomerHubId;  
    			$customerData->id = $remoteCustomerHubId;
    			if($this->getSessionId())
    			{
    				$customerData->session = $this->getSessionId();
    				$this->_setRemoteCustomerHubSession($customerData);
    			}    			
    		}
    	}       	    	
    	return $this;
    }
    
    
	protected function _composeHubEvent()
	{		
    	if(!$this->_eventForHub)
    	{
    		$this->_eventForHub = new stdClass();
    	}    	    	
    	$this->_eventForHub->type = $this->getName();
    	$this->_eventForHub->date = date(DATE_ISO8601, strtotime($this->getCreatedAt()));    	
    	$this->_eventForHub->context = "ECOMMERCE";    	    	       	
    	$store = Mage::getModel('core/store')->load($this->getStoreId());
    	$contextInfo = new stdClass();
    	$objStore = new stdClass();
    	$objStore->id = "".$this->getStoreId();
    	$objStore->name = $store->getName();
    	$objStore->country = "".Mage::getStoreConfig('general/country/default', $this->getStoreId());
    	$objStore->website = Mage::getUrl('', array('_store' => $this->getStoreId()));
    	$objStore->type = "ECOMMERCE"; 
    	$contextInfo->store = $objStore;
    	$client = new stdClass();
    	if($this->getEnvUserAgent())
    	{    		
    		$client->userAgent = "".$this->getEnvUserAgent();
    	}
    	if($this->getEnvRemoteIp())
    	{
    		$client->ip = "".$this->getEnvRemoteIp();
    	}
    	$contextInfo->client = $client;
    	$this->_eventForHub->contextInfo = $contextInfo;
    	
    	if($this->_remoteCustomerHubId)
    	{
    		$this->_eventForHub->customerId = $this->_remoteCustomerHubId;
    	}
    	else
    	{
    		$bringBackProperties = new stdClass();
    		$bringBackProperties->type = "SESSION_ID";
    		$bringBackProperties->value = $this->getSessionId();
    		$bringBackProperties->nodeId = $this->_helper()->getConfigData('settings/apinodeid', $this->getStoreId());
    		$this->_eventForHub->bringBackProperties = $bringBackProperties;
    	}
    	
    	return $this->_eventForHub;
    }
    
    protected function _getCategoryNamesFromIds($catIds)
    {
    	$result = array();
    	foreach ($catIds as $catId) 
    	{
    		$_category = Mage::getModel('catalog/category')->load($catId);
    		if ($_category) 
    		{
    			$result[] = $_category->getName();
    		}    		
    	}
    	return $result;
    }
    
    protected function _getObjProduct($product_id)
    {
	    $product = Mage::getModel('catalog/product')->load($product_id);
	    $objProduct = new stdClass();	    
	    if($product)
	    {
		    $objProduct->id = $product->getEntityId();
		    $objProduct->sku = $product->getSku();
		    $objProduct->name = $product->getName();
		    $objProduct->price = (float)Mage::getModel('directory/currency')->formatTxt($product->getPrice(), array( 'display' => Zend_Currency::NO_SYMBOL ));
		    $objProduct->imageUrl = Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
		    $objProduct->linkUrl = Mage::getUrl($product->getUrlPath());;
		    $objProduct->shortDescription = $product->getShortDescription();
		    $objProduct->category = $this->_getCategoryNamesFromIds($product->getCategoryIds());
	    }
	    return $objProduct;
    }
    
    protected function _getRemoteCustomerHub($customerData)
    {
    	$remoteCustomerHub = null;    
    	if($this->getIdentityEmail())
    	{
    		$remoteCustomerHub = $this->_getHub()->getRemoteCustomerHub($customerData);
	    	if ($remoteCustomerHub->id) 
	    	{	    		
	    		return $remoteCustomerHub->id;
	    	}
    	}    	
    	return $remoteCustomerHub;
    }
    
    protected function _setRemoteCustomerHubSession($customerData)
    {
    	$remoteCustomerHubSession = $this->_getHub()->setRemoteCustomerHubSession($customerData);
    	return $this;
    }
    
    protected function _getCustomerDataForHub()
   	{
   		$customerData = new stdClass();
   		$customerData->nodeId = $this->_helper()->getConfigData('settings/apinodeid', $this->getStoreId());
   		$base = new stdClass();
   		$contacts = new stdClass();
   		$contacts->email = $this->getIdentityEmail();   	
   		$base->contacts = $contacts;
   		$locale = Mage::getStoreConfig('general/locale/code', $this->getStoreId());
   		//$customerData->extra->locale = !empty($tmpval) ? $tmpval : '';
   		if (!empty($locale)) 
   		{
   			$base->locale = $locale;
   		}
   		$websiteId = Mage::getModel('core/store')->load($this->getStoreId())->getWebsiteId();
   		$customer = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->loadByEmail($this->getIdentityEmail());
   		if ($customer) 
   		{   			
   			if ($customer->getPrefix()) 
   			{
   				$base->title = $customer->getPrefix();
   			}
   			if ($customer->getFirstname()) 
   			{
   				$base->firstName = $customer->getFirstname();
   			}
   			if ($customer->getLastname()) 
   			{
   				$base->lastName = $customer->getLastname();
   			}
   			if ($customer->getGender())
   			{
   				$base->gender = $customer->getGender() == 1 ? 'Male' : 'Female';
   			}   			
   			if ($customer->getDob()) 
   			{   				
   				$base->dob = date('Y-m-d', strtotime($customer->getDob()));
   			}
   			$customerAddressId = $customer->getDefaultBilling();
   			
   			if (intval($customerAddressId)) 
   			{   		
   				$objAddress = new stdClass();
   				$address = Mage::getModel('customer/address')->load($customerAddressId);
   				if ($address->getCity()) 
   				{
   					$objAddress->city = $address->getCity();
   				}
   				$street = $address->getStreet();
   				if (!empty($tmpval))
   				{
   					$tmpval = is_array($street) ? $street[0] : $street;
   					$objAddress->street = $street ?: '';
   				}
   				if ($address->getRegion())
   				{
   					$objAddress->province = $address->getRegion();
   				}
   				if ($address->getPostcode()) 
   				{
   					$objAddress->zip = $address->getPostcode();
   				}   				
   				if ($address->getCountry()) 
   				{
   					$country = Mage::getModel('directory/country')->load($address->getCountry())->getName();
   					$objAddress->country = $country ?: '';
   				}
   				$base->address = $objAddress;
   			}   			   			
   		}
   		$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($this->getIdentityEmail());
   		if ($subscriber->getId()) 
   		{
   			$subcriberObj = new stdClass();
   			$subcriberObj->id = $this->_helper()->getConfigData('events/campaignName', $this->getStoreId());
   			$subcriberObj->kind = "DIGITAL_MESSAGE";
   			$tmpval = $subscriber->getData('subscriber_status') == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
   			$subcriberObj->subscribed = $tmpval ? true : false;
   			$subcriberObj->subscriberId = $subscriber->getSubscriberId();
   			
			$subcriberObj->updatedAt = date(DATE_ISO8601, strtotime($this->getCreatedAt()));
			$subcriberObj->registeredAt = date(DATE_ISO8601, strtotime($subscriber->getCreatedAt()));
			$subcriberObj->startDate = date(DATE_ISO8601, strtotime($subscriber->getLastSubscribedAt()));    			 
   			if($subcriberObj->subscribed)
   			{   		   				
   				$subcriberObj->endDate = null;
   			}
   			else
   			{      				
   				$subcriberObj->endDate = date('Y-m-d', strtotime($this->getCreatedAt()));
   			}   			
   			$subscriptions[] = $subcriberObj;
   			$base->subscriptions = $subscriptions;   			
   		}   	
   		$customerData->base = $base;
   		return $customerData;
   	}   	
   
}
