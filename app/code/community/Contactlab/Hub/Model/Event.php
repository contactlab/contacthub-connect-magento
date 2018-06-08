<?php
class Contactlab_Hub_Model_Event extends Mage_Core_Model_Abstract
{
    
    const CONTACTLAB_HUB_STATUS_TO_EXPORT   = 0;
    const CONTACTLAB_HUB_STATUS_EXPORTED    = 1;
    const CONTACTLAB_HUB_STATUS_PROCESSING  = 2;
    const CONTACTLAB_HUB_STATUS_FAILED      = 3;

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
        if (!$this->_helper) {
            $this->_helper = Mage::helper('contactlab_hub');
        }
        return $this->_helper;
    }
    
    public function getUnexportedEvents($pageSize = null)
    {
        if (!$this->_unexportedEvents) {
            $collection = $this->getCollection()
                        ->addFieldToSelect('entity_id')
                        ->addFieldToSelect('model')
                        ->addFieldToFilter('status', array('eq' => self::CONTACTLAB_HUB_STATUS_TO_EXPORT));
            if ($pageSize) {
                $collection->getSelect()->limit($pageSize);
            }
            $unexportedEvents = array();
            foreach ($collection as $event) {
                $unexportedEvents[] = Mage::getModel('contactlab_hub/event_'.$event->getModel())->load($event->getEntityId());
            }
            $this->_unexportedEvents = $unexportedEvents;
        }
        return $this->_unexportedEvents;
    }
   
    public function trace()
    {
        //$this->_helper()->log(__METHOD__);
        $this->_assignData();
        $websiteId = Mage::getModel('core/store')->load($this->getStoreId())->getWebsiteId();
        $customer = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->loadByEmail($this->getIdentityEmail());
        if ($this->_helper()->getConfigData('events/'.$this->getName(), $this->getStoreId())) {
            if ($customer || $this->_helper()->sendAnonymousEvent()) {
                $this->save();
            } else {
                $this->_helper()->log('events/send anonymous events OFF');
            }
        }
        return $this;
    }
    
    
    public function export()
    {
        $this->_helper()->log(__METHOD__);
        try {
            $this->setStatus(self::CONTACTLAB_HUB_STATUS_PROCESSING);
            $this->_createUpdateCustomer();
            $hubEvent = $this->_composeHubEvent();
            $this->_getHub()->createEvent($hubEvent);
            $this->setExportedDate(date('Y-m-d H:i:s'));
            $this->setStatus(self::CONTACTLAB_HUB_STATUS_EXPORTED);
            $this->save();
        } catch (exception $e) {
            $this->setStatus(self::CONTACTLAB_HUB_STATUS_FAILED);
            $this->setHubEvent(json_encode($hubEvent));
            $this->save();
            $this->_helper()->log($e->getMessage());
        }

        $this->_helper()->log('fine export event');
        return $this;
    }
    
    protected function _getSid()
    {   
        $cookieModel = Mage::getModel('core/cookie');
        $cookie = json_decode($cookieModel->get('_ch'), true);

        if ($cookie && $cookie['sid']) {
            $this->setSessionId($cookie['sid']);
        } else if (!$this->_helper()->isJsTrackingEnabled()) {
            $this->setSessionId($this->_helper()->createTrackingCookie());
        }
        
        if (!$this->getSessionId()) {
            $this->_helper()->log('Cookie disabled');
        }

        return $this->getSessionId();
    }
    
    protected function _assignData()
    {   
        $this->setCreatedAt(date('Y-m-d H:i:s'))
            ->setEnvUserAgent($this->_helper()->getUserAgent());
            
        if (!$this->getEnvRemoteIp()) {
            $this->setEnvRemoteIp($this->_helper()->getRemoteIpAddress());
        }
        if (!$this->getNeedUpdateIdentity()) {
            $this->setNeedUpdateIdentity(false);
        }
        if (!$this->getStoreId()) {
            $this->setStoreId(Mage::app()->getStore()->getStoreId());
        }
        if ($customer = $this->_helper()->getCustomer()) {
            $this->setIdentityEmail($customer->getEmail());
        }
        return $this;
    }
    
    protected function _getHub()
    {
        if (!$this->_hub) {
            $this->_hub = Mage::getModel('contactlab_hub/hub')->setStoreId($this->getStoreId());
        }
        return $this->_hub;
    }

    public function _createUpdateCustomer()
    {
        $this->_helper()->log(__METHOD__);
        
        $this->_remoteCustomerHubId = null;
        if ($this->getNeedUpdateIdentity()) {
            $customerData = $this->_getCustomerDataForHub();
            $remoteCustomerHubId = $this->_getRemoteCustomerHub($customerData);
            if ($remoteCustomerHubId) {
                $this->_remoteCustomerHubId = $remoteCustomerHubId;
                $customerData->id = $remoteCustomerHubId;
                if ($this->getSessionId()) {
                    $customerData->session = $this->getSessionId();
                    $this->_setRemoteCustomerHubSession($customerData);
                }
            }
        }
        return $this;
    }
    
    protected function _composeHubEvent()
    {
        if (!$this->_eventForHub) {
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
        if ($this->getEnvUserAgent()) {
            $client->userAgent = "".$this->getEnvUserAgent();
        }
        if ($this->getEnvRemoteIp()) {
            $client->ip = "".$this->getEnvRemoteIp();
        }
        $contextInfo->client = $client;
        $this->_eventForHub->contextInfo = $contextInfo;
        
        if ($this->_remoteCustomerHubId) {
            $this->_eventForHub->customerId = $this->_remoteCustomerHubId;
        } else {
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
        foreach ($catIds as $catId) {
            $_category = Mage::getModel('catalog/category')->load($catId);
            if ($_category)
            {
                $result[] = $_category->getName();
            }
        }
        return $result;
    }
    
    protected function _getObjProduct($productId, $storeId = null)
    {
        $product = Mage::getModel('catalog/product');    
        if($storeId)
        {
            $product->setStoreId($storeId);
        }
        $product->load($productId);
        return $this->_toHubProduct($product);
    }

    protected function _toHubProduct(Mage_Catalog_Model_Product $product) {
        $objProduct = new stdClass();
        if ($product == null) {
            return $objProduct;
        }
        $objProduct->id = $product->getEntityId();
        $objProduct->sku = $product->getSku();
        $objProduct->name = $product->getName();
        // Questo è il price che viene mostrato 
        // negli eventi relativi al carrello (aggiunta e rimozione)
        // e aggiunta e rimozione a wishlist
        // Il prezzo dell'ordine viene ricalcolato nella classe specializzata Event/Checkout.php
        $objProduct->price = (float)Mage::getModel('directory/currency')->formatTxt($product->getPrice(), array( 'display' => Zend_Currency::NO_SYMBOL ));
        // Fine moficihe prezzo
        $objProduct->imageUrl = ''.Mage::helper('catalog/image')->init($product, 'image');
        $objProduct->linkUrl = Mage::app()->getStore($product->getStoreId())->getBaseUrl().$product->getUrlPath();
        $objProduct->shortDescription = $product->getShortDescription() ?: "";
        $objProduct->category = $this->_getCategoryNamesFromIds($product->getCategoryIds());
  /*
        INIZIO CUSTOMIZZAZIONE
        Per portare a casa i valori che mancano occorre customizzare 
        questa sezione
        */
        $objProduct->vendor = "Alberta Ferretti";
        $objProduct->classifications = array(
            // Plan as strings
            array(
                'key'   => 'saleContext',
                'value' => 'Vendita magazzino', // Valori possibili: , 'Vendita magazzino', 'Vendita ufficio', 'Vendita rinascente', 'Sposa - vendita magazzino', 'Sposa - vendita ufficio', 'Sposa - vendita rinascente'], 1),
            ),
            array(
                'key'   => 'unique',
                'value' => 'AF-001',
            ),
            array(
                'key'   => 'codicedx',
                'value' => 'DX-123',
            ),
            array(
                'key'   => 'collection',
                'value' => 'AI18',
            ),
            array(
                'key'   => 'saleContract',
                'value' => '0 - Acquisto', // Uno tra  '0 - Acquisto', '1 - Prova', '4 - Noleggio 4 giorni', '8 - Noleggio 8 giorni'
            ),
            array(
                'key'   => 'event',
                'value' => 'Cerimonia', // Oppure - ad esempio - 'Cocktail', 'Matrimoni' , 'Serata romantica'
            ),
            array(
                'key'   => 'length',
                'value' => 'Al ginocchio',
            ),
            array(
                'key'   => 'size',
                'value' => '42',
            ),
            array(
                'key'   => 'color',
                'value' => 'Rosso',
            ),
            // Plan as dates
            array(
                'key'   => 'rentStart',
                'value' => '2018-05-28',
            ),
            array(
                'key'   => 'rentEnd',
                'value' => '2018-06-01',
            ),
            // Storage only
            array(
                'key' => 'membership',
                'value' => '0 - No membership', // Altri valori possibili  '0 - No membership', '1 - Plus', '2 - Gold', '3 - Unlimited'], 1)
            ),
            array(
                'key' => 'type',
                'value' => 'simple',
            ),
            array(
                'key' => 'visibility',
                'value' => '0'
            ),
            array(
                'key' => 'status',
                'value' => '1'
            ),
            array(
                'key' => 'shape',
                'value' => 'Pera',
            ),
            array(
                'key' => 'model',
                'value' => 'Abito a palloncino',
            ),
            array(
                'key' => 'neckline',
                'value' => 'Monospalla',
            ),
            array(
                'key' => 'material',
                'value' => 'Cotone',
            ),
            array(
                'key' => 'hiddenRehersal',
                'value' => 'Si',
            ),
            array(
                'key' => 'additionalSize',
                'value' => '44',
            )
        );
        /*
        FINE CUSTOMIZZAZIONE
        */
        return $objProduct;
    }
    
    protected function _getRemoteCustomerHub($customerData)
    {
        $remoteCustomerHub = null;
        if ($this->getIdentityEmail()) {
            $remoteCustomerHub = $this->_getHub()->getRemoteCustomerHub($customerData);
            if ($remoteCustomerHub->id) {
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
        if (!empty($locale)) {
            $base->locale = $locale;
        }
        $websiteId = Mage::getModel('core/store')->load($this->getStoreId())->getWebsiteId();
        $customer = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->loadByEmail($this->getIdentityEmail());
        if ($customer) {
            /*
            if ($customer->getPrefix()) {
                $base->title = $customer->getPrefix();
            }
            if ($customer->getFirstname()) {
                $base->firstName = $customer->getFirstname();
            }
            if ($customer->getLastname()) {
                $base->lastName = $customer->getLastname();
            }
            if ($customer->getGender()) {
                $base->gender = $customer->getGender() == 1 ? 'Male' : 'Female';
            }
            if ($customer->getDob()) {
                $base->dob = date('Y-m-d', strtotime($customer->getDob()));
            }
            */
            $customerAddressId = $customer->getDefaultBilling();
            
            if (intval($customerAddressId)) {
                $objAddress = new stdClass();
                $address = Mage::getModel('customer/address')->load($customerAddressId);
                if ($address->getCity()) {
                    $objAddress->city = $address->getCity();
                }
           		$street = '';
   				if(is_array($address->getStreet())){
   					foreach ($address->getStreet() as $str){
   						$street.= $str.' ';
   					}
   					$street = trim($street);
   				}else{ 
   					$street = $street.$address->getStreet();
   				}   
   				$objAddress->street = $street;
                if ($address->getRegion()) {
                    $objAddress->province = $address->getRegion();
                }
                if ($address->getPostcode()) {
                    $objAddress->zip = $address->getPostcode();
                }
                if ($address->getCountry()) {
                    $country = Mage::getModel('directory/country')->load($address->getCountry())->getName();
                    $objAddress->country = $country ?: '';
                }
                $base->address = $objAddress;
            }
            $extraBaseProperties = $this->_helper()->getExtraProperties($customer, 'base');
            $base = (object) array_merge( (array)$base, $extraBaseProperties );
        }
        if (in_array($this->getName(), array('campaignSubscribed', 'campaignUnsubscribed'))) {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($this->getIdentityEmail());
            if ($subscriber->getId()) {
                $subcriberObj = new stdClass();
                $subcriberObj->id = $this->_helper()->getConfigData('events/campaignName', $this->getStoreId());
                $subcriberObj->kind = "DIGITAL_MESSAGE";
                $tmpval = $subscriber->getData('subscriber_status') == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
                $subcriberObj->subscribed = $tmpval ? true : false;
                $subcriberObj->subscriberId = $subscriber->getSubscriberId();
                
                $subcriberObj->updatedAt = date(DATE_ISO8601, strtotime($this->getCreatedAt()));
                $subcriberObj->registeredAt = date(DATE_ISO8601, strtotime($subscriber->getCreatedAt()));
                $subcriberObj->startDate = date(DATE_ISO8601, strtotime($subscriber->getLastSubscribedAt()));
                if ($subcriberObj->subscribed) {
                    $subcriberObj->endDate = null;
                } else {
                    $subcriberObj->endDate = date(DATE_ISO8601, strtotime($this->getCreatedAt()));
                }
                $subscriptions[] = $subcriberObj;
                $base->subscriptions = $subscriptions;
            }
        }

        $externalId = $this->_helper()->getExternalId($customer);
        if($externalId)
        {
            $customerData->externalId = $externalId;
        }

        $customerData->base = $base;

        $extraExtendedProperties = $this->_helper()->getExtraProperties($customer, 'extended');
        /* 
        INIZIO CUSTOMIZZAZIONE

        In questo blocco vanno configurati tutti i campi che non è possibile mappare da backend magento.
        Il processo ideale quindi è:

        1) configurare i dati extra necessari per il customer
        2) mapparli dove possibile nel backend
        3) quelli rimanenti estenderli come da esempio sotto
        */
        $extraExtendedProperties['website_id']        = 1;
        $extraExtendedProperties['utm_source']        = 'Facebook';
        $extraExtendedProperties['utm_campaign']      = 'Lead Ads 2018';
        $extraExtendedProperties['city']              = 'Milano';
        $extraExtendedProperties['first_order_date']  = '2018-02-25';
        $extraExtendedProperties['repeater']          = 1;
        $extraExtendedProperties['date_repeater']     = '2018-04-03';
        /*
        FINE CUSTOMIZZAZIONE
        */
        if (count($extraExtendedProperties) > 0) {
            $customerData->extended = (object) $extraExtendedProperties;
        }

        return $customerData;
    }
    
    public function cleanEvents()
    {
        $months = $this->_helper()->getMonthsToClean();
        $time = strtotime(date("Y-m-d"));
        $date = date("Y-m-d", strtotime("-".$months." month", $time));
        $collection = $this->getCollection()       
                    ->addFieldToFilter('status', array('in' => array(self::CONTACTLAB_HUB_STATUS_EXPORTED, self::CONTACTLAB_HUB_STATUS_FAILED)))
                    ->addFieldToFilter('created_at', array('lt' => $date));
        foreach($collection as $event)
        {
            $event->delete();
        }                   
    }
}
