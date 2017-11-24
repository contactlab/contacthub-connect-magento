<?php
/**
*
*/
class Contactlab_Hub_Helper_Data extends Mage_Core_Helper_Abstract
{
    const JS_TRACKING_ENABLED_CONFIG_PATH = 'contactlab_hub/js_tracking/enabled';
    const JS_UNTRUSTED_TOKEN_CONFIG_PATH = 'contactlab_hub/js_tracking/untrusted_token';

    protected $_saveLog = false;
    protected $_logFilename = false;

    public function __construct()
    {
        $this->_saveLog = $this->getConfigData('settings/log');
        $this->_logFilename = $this->getConfigData('settings/logfilename')?:'contactlabhub.log';
    }

    public function getConfigData($key, $storeId = null)
    {
        if (!$storeId) {
            $storeId = $this->getStore()->getStoreId();
        }
        return $this->getConfigStoredData($key, $storeId);
    }

    public function getConfigStoredData($key, $storeId = null)
    {
        return Mage::getStoreConfig('contactlab_hub/'.$key, $storeId);
    }

    public function getConfigDefaultData($key)
    {
        return Mage::getConfig()->getNode('default/contactlab_hub/'.$key);
    }

    public function setConfigData($path, $value, $scope = 'default', $scopeId = 0)
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
        if (!$this->_saveLog && $level == null) {
            return false;
        }
        Mage::log($message, $level, $this->_logFilename);
    }

    /**
    * @return bool
    */
    public function isJsTrackingEnabled()
    {
        return Mage::getStoreConfigFlag(self::JS_TRACKING_ENABLED_CONFIG_PATH);
    }

    /**
    * @return bool
    */
    public function getApiTokenForJavascript()
    {
        return Mage::getStoreConfig(self::JS_UNTRUSTED_TOKEN_CONFIG_PATH) ?: $this->getConfigData('settings/apitoken');
    }

    public function deleteTrackingCookie()
    {
        Mage::getSingleton('core/cookie')->set('_ch', '', -1, '/', '');
        unset($_COOKIE['_ch']);
    }

    /**
    * Creates a tracking cookie, used when JS tracking is disabled.
    * @return string
    */
    public function createTrackingCookie()
    {
        $cookieData = array('sid' => uniqid());
        $customer = $this->getCustomer();
        if ($customer != null) {
            $cookieData['customerId'] = $customer->getId();
        }
        Mage::getModel('core/cookie')->set('_ch', json_encode($cookieData), 31536000, '/', '');
        
        return $cookieData['sid'];
    }
    
    public function getJsConfigData()
    {
        $config = new stdClass();
        $config->workspaceId = $this->getConfigData('settings/apiworkspaceid');
        $config->nodeId = $this->getConfigData('settings/apinodeid');
        $config->token = $this->getApiTokenForJavascript();
        $config->context = 'ECOMMERCE';
        $contextInfo = new stdClass();
        $store = new stdClass();
        $store->id = "".Mage::app()->getStore()->getStoreId();
        $store->name = Mage::app()->getStore()->getName();
        $store->country = Mage::getStoreConfig('general/country/default');
        $store->website = Mage::getUrl('', array('_store' => Mage::app()->getStore()->getStoreId()));
        $store->type = "ECOMMERCE";
        $contextInfo->store = $store;
        $config->contextInfo = $contextInfo;
        return "\nch('config', ".json_encode($config).");";
    }
    
    public function getCustomer()
    {
        $customer = null;
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')->load(Mage::getSingleton('customer/session')->getCustomer()->getId());
        }
        return $customer;
    }
    
    protected function _getJsCoustomerInfo()
    {
        if (!$customer = $this->getCustomer()) {
            return null;
        }
        $customerInfo = new stdClass();
        $base = new stdClass();
        $base->firstName = $customer->getFirstname();
        $base->lastName = $customer->getLastname();
        $contacts = new stdClass();
        $contacts->email = $customer->getEmail();
        $base->contacts = $contacts;
        $customerInfo->base = $base;
        return "\nch('customer',".json_encode($customerInfo).");";
    }
    
    public function getCategoryPageTracking()
    {
        $category = Mage::registry('current_category');
        $tracking = "";
        $evtName = 'events/viewedProductCategory';
        if ($this->getConfigData($evtName)) {
            $searchQuery = Mage::app()->getRequest()->getParam('q');
            $currentLayer = Mage::registry('current_layer');
            $searchResult = ($currentLayer instanceof Varien_Object)?$currentLayer->getProductCollection()->getAllIds():array();
            $tracking.= "";
            $tracking.= $this->_getJsCoustomerInfo();
            $categoryJs = new stdClass();
            $categoryJs->type = 'viewedProductCategory';
            $categoryJs->additionalProperties = false;
            $properties = new stdClass();
            $properties->category = $this->clearStrings($category->getName());
            $categoryJs->properties = $properties;
            $tracking.= "\nch('event',".json_encode($categoryJs).");";
        } else {
            $this->log($evtName.' OFF');
        }
        return $tracking;
    }
    
    public function getProductPageTracking()
    {
        $tracking = "";
        $evtName = 'events/viewedProduct';
        if ($this->getConfigData($evtName)) {
            $product = Mage::registry('current_product');
            $categories = array();
            foreach ($product->getCategoryIds() as $categoryId) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $categories[] = $category->getName();
            }
            $tracking.= "";
            $tracking.= $this->_getJsCoustomerInfo();
            $productJs = new stdClass();
            $productJs->type = 'viewedProduct';
            $properties = new stdClass();
            $properties->id = $product->getEntityId();
            $properties->sku = $product->getSku();
            $properties->name = $this->clearStrings($product->getName());
            $properties->price = round($product->getFinalPrice(), 2);
            $properties->imageUrl = ''.Mage::helper('catalog/image')->init($product, 'image');
            $properties->linkUrl = $product->getProductUrl();
            $properties->shortDescription = $this->clearStrings($product->getShortDescription());
            $properties->category = $categories;
            $productJs->properties = $properties;
            $tracking.= "\nch('event',".json_encode($productJs).");";
        } else {
            $this->log($evtName.' OFF');
        }
        return $tracking;
    }
    
    public function getSearchTracking()
    {
        $tracking = "";
        $evtName = 'events/searched';
        if ($this->getConfigData($evtName)) {
            $searchJs = new stdClass();
            $searchQuery = Mage::app()->getRequest()->getParam('q');
            $currentLayer = Mage::registry('current_layer');
            $searchResult = ($currentLayer instanceof Varien_Object) ? count($currentLayer->getProductCollection()->getAllIds()) : 0;
            $tracking.= "";
            $tracking.= $this->_getJsCoustomerInfo();
            $searchJs->type = 'searched';
            $properties = new stdClass();
            $properties->keyword = $this->clearStrings($searchQuery);
            $properties->resultCount = $searchResult;
            $searchJs->properties = $properties;
            $tracking.= "\nch('event',".json_encode($searchJs).");";
        } else {
            $this->log($evtName.' OFF');
        }
        return $tracking;
    }
    
    public function clearStrings($string)
    {
        return trim(str_replace("''", "", str_replace("\n", " ", strip_tags($string))));
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
    
    public function getExchangeRate($storeId = null)
    {
        $exchangeRate = 1;
        $baseCurrency = $this->getConfigStoredData('events/base_currency', $storeId);
        $websiteCurrency = $this->getConfigStoredData('events/website_currency', $storeId);
        if ($baseCurrency != $websiteCurrency) {
            $exchangeRate = (float)$this->getConfigStoredData('events/exchange_rate', $storeId);
            if (!$exchangeRate) {
                $exchangeRate = 1;
            }
        }
        return $exchangeRate;
    }
    
    public function convertToBaseRate($price, $exchangeRate)
    {
        return round(((float)$price / $exchangeRate), 2);
    }
    
    public function getOrderStatusToBeSent($storeId)
    {
    	   return explode(',', $this->getConfigStoredData('events/order_status', $storeId));
    }
    
    public function getExtraProperties($customer)
    {
        $extraPropreties = array();
        for ($i=1; $i<6; $i++) {
            $hubFixed = $this->getConfigStoredData('extra_properties/hub_fixed_'.$i);
            $magentoFixed = $this->getConfigStoredData('extra_properties/magento_fixed_'.$i);
            if ($hubFixed && $magentoFixed) {
                $valueFixed = $this->_getCustomerAttributeValue($magentoFixed, $customer);
                $extraPropreties[$hubFixed] = $valueFixed;
            }
            $hubVariable = $this->getConfigStoredData('extra_properties/hub_variable_'.$i);
            $magentoVariable = $this->getConfigStoredData('extra_properties/magento_variable_'.$i);
            if ($hubVariable && $magentoVariable) {
                $valueVariable = $this->_getCustomerAttributeValue($magentoVariable, $customer);
                $extraPropreties[$hubVariable] = $valueVariable;
            }
        }
        return $extraPropreties;
    }
    
    protected function _getCustomerAttributeValue($attributeCode, $customer)
    {
        $value = '';
        
        if ($customer) {
            $attribute = Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('attribute_code', array('in' => $attributeCode) )->getFirstItem();
                            
            if ($attribute) {
                if ($attribute->getEntityTypeId() == 1) {
                    if ($attribute->getBackendType() == 'int') {
                        $value = Mage::getResourceSingleton('customer/customer')
                        ->getAttribute($attributeCode)
                        ->getSource()
                        ->getOptionText($customer->getData($attributeCode));
                    } else {
                        $value.= $customer->getData($attributeCode);
                    }
                } else {
                    /* BILLING INFORMATIONS */
                    $billing = $customer->getDefaultBillingAddress();
                    if ($billing) {
                        if ($billing->getData($attributeCode)) {
                            if ($attribute->getBackendType() == 'int') {
                                $value = Mage::getResourceSingleton('customer/address')
                                ->getAttribute($attributeCode)
                                ->getSource()
                                ->getOptionText($billing->getData($attributeCode));
                            } else {
                                $value.= $billing->getData($attributeCode);
                            }
                        }
                    }
                    /* SHIPPING INFORMATIONS
					$shipping = $customer->getDefaultShippingAddress();
					if($shipping)
					{
						if($shipping->getData($attributeCode))
						{
							if($attribute->getBackendType() == 'int')
							{
								$value = Mage::getResourceSingleton('customer/address')
								->getAttribute($attributeCode)
								->getSource()
								->getOptionText($shipping->getData($attributeCode));						
							}
							else
							{
								$value = $shipping->getData($attributeCode);
							}
						}
					}
					*/
                }
            }
        }
        return $value;
    }
    
    public function getMonthsToClean()
    {
        return 1;
    }
}
