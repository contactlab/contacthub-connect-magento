<?php

/**
 * Test block to queue task.
 */
class Contactlab_Hub_Block_Adminhtml_Version extends Mage_Adminhtml_Block_Abstract {

    const STATUS_KO = 3;
    const STATUS_IMPROVBLE = 0;
    const STATUS_OK = 1;


    const PHP_MIN_VERSION = '5.5';
    const MAGENTO_MIN_VERSION = '1.7.0';
    const HUB_LATEST_VERSION_FILE = 'https://raw.githubusercontent.com/contactlab/contacthub-connect-magento/master/app/code/community/Contactlab/Hub/etc/config.xml';

    /**
     * Construct the block.
     */
    public function __construct() {
        $this->setTemplate("contactlab/hub/version.phtml");
        parent::__construct();
    }
    
    public function checkPhpVersion()
    {
        $check = [
            'code' => self::STATUS_KO,
            'message' => Mage::helper('contactlab_hub')->__(
                'Your PHP version is not compatible with this module. Please update PHP to %s version',
                self::PHP_MIN_VERSION
            )
        ];
        if(version_compare(phpversion(), self::PHP_MIN_VERSION) >= 0)
        {
            $check = ['code' => self::STATUS_OK, 'message' => __('Your PHP version is compatible with this module')];
        }
        return $check;
    }

    public function checkMagentoVersion()
    {

        $check = [
            'code' => self::STATUS_KO,
            'message' => Mage::helper('contactlab_hub')->__(
                'Your Magento version is not compatible with this module. Please update Magento to %s version',
                self::MAGENTO_MIN_VERSION
            )
        ];
        if(version_compare(Mage::getVersion(), self::MAGENTO_MIN_VERSION) >= 0)
        {
            $check = ['code' => self::STATUS_OK, 'message' => __('Your Magento version is compatible with this module')];
        }
        return $check;
    }

    public function getModuleVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Contactlab_Hub->version;;
    }

    public function checkModuleVersion()
    {
        $check = [
            'code' => self::STATUS_IMPROVBLE,
            'message' => Mage::helper('contactlab_hub')->__(
                'We can\'t automatically check if Contactlab_Hub is installed at the latest version. 
                Please check your version on github'
            )
        ];
        if($xml = simplexml_load_file(self::HUB_LATEST_VERSION_FILE))
        {
            if (version_compare($this->getModuleVersion(), $xml->modules->Contactlab_Hub->version) >= 0) {
                $check = ['code' => self::STATUS_OK, 'message' => __('Contactlab_Hub is installed at the latest version')];
            }
            else
            {
                $check = [
                    'code' => self::STATUS_KO,
                    'message' => Mage::helper('contactlab_hub')->__(
                        'Contactlab_Hub version is not installed at the latest version. Please update to %s version',
                        $xml->modules->Contactlab_Hub->version
                    )
                ];
            }
        }
        return $check;
    }

    public function checkCron()
    {
        $check = ['code' => self::STATUS_KO, 'message' => Mage::helper('contactlab_hub')->__('Magento Cron is not running')];
        $datetime1 = new DateTime;
        foreach ($this->_getCronSchedules() as $cronSchedule)
        {
            $datetime2 = new DateTime($cronSchedule->getCreatedAt());
            $interval = round((strtotime($datetime1) - strtotime($datetime2)) / 60);
            if($interval > 5)
            {
                $intervalTime = $interval;
                $time = 'minutes';
                if($interval > 120) //hours
                {
                    $intervalTime = round($interval / 60);
                    $time = 'hours';
                }
                if($interval > 2880) //days
                {
                    $intervalTime = round($interval / 1440);
                    $time = 'days';
                }
                $check = [
                    'code' => self::STATUS_IMPROVBLE,
                    'message' => Mage::helper('contactlab_hub')->__('Magento Cron is not running until %s %s', $intervalTime, $time)
                ];
            }
            else
            {
                $check = ['code' => self::STATUS_OK,'message' => Mage::helper('contactlab_hub')->__('Magento Cron runs properly')];
            }
        }

        return $check;
    }

    protected function _getCronSchedules()
    {
        return Mage::getModel('cron/schedule')->getCollection()
                ->setPageSize(1)
                ->setCurPage(1)
                ->setOrder('created_at','DESC')
                ->load();
    }


    public function checkApi()
    {
        $check = ['code' => self::STATUS_KO, 'message' => Mage::helper('contactlab_hub')->__('API not properly configured')];
        $apiToken = Mage::helper('contactlab_hub')->getConfigData('settings/apitoken');
        $apiWorkspace = Mage::helper('contactlab_hub')->getConfigData('settings/apiworkspaceid');
        $apiNodeId = Mage::helper('contactlab_hub')->getConfigData('settings/apinodeid');
        $apiUrl = Mage::helper('contactlab_hub')->getConfigData('settings/apiurl');

        if(($apiToken) && ($apiWorkspace) && ($apiNodeId) && ($apiUrl))
        {
            $response = Mage::getModel('contactlab_hub/hub')->getAllCustomers();
            if($response->curl_http_code != '200')
            {
                $check = ['code' => self::STATUS_IMPROVBLE, 'message' => Mage::helper('contactlab_hub')->__('There was an error with your API configuration')];
            }
            else
            {
                $check = ['code' => self::STATUS_OK, 'message' => Mage::helper('contactlab_hub')->__('API works properly')];
            }
        }
        return $check;
    }
}
