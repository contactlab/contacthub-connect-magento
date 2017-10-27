<?php
class Contactlab_Hub_Model_Hub extends Mage_Core_Model_Abstract
{
    const API_VERSION = 'hub/v1/workspaces/';
    
    protected $_helper = null;
    
    protected function _getApiToken()
    {
        return $this->_helper()->getConfigData('settings/apitoken', $this->getStoreId());
    }

    protected function _getApiWorkspace()
    {
        return $this->_helper()->getConfigData('settings/apiworkspaceid', $this->getStoreId());
    }
    
    protected function _getNodeId()
    {
        $this->_helper()->getConfigData('settings/apinodeid', $this->getStoreId());
    }
    
    protected function _getApiProxy()
    {
        return $this->_helper()->getConfigData('settings/useproxy', $this->getStoreId()) ? $this->_helper()->getConfigData('settings/apiproxy', $this->getStoreId()) : false;
    }
    
    public function getRemoteCustomerHub($data)
    {
        $this->_helper()->log(__METHOD__);
        try {
            $response = $this->curlPost($this->_getApiUrl('customers'), json_encode($data), true);
            $response = json_decode($response);
            if ($response->curl_http_code == 409) {
                unset($data->nodeId);
                $data->id = $response->data->customer->id;
                //unset($data->subscriptions);
                $response = $this->curlPost($response->data->customer->href, json_encode($data), true, null, "PATCH");
                return json_decode($response);
            } else {
                return $response->id;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    public function setRemoteCustomerHubSession($data)
    {
        $this->_helper()->log(__METHOD__);
            $url = $this->_getApiUrl('customers').'/'.$data->id.'/sessions';
            $session = $data->session;
            $data = new stdClass();
            $data->value = $session;
            $response = $this->curlPost($url, json_encode($data), true);
            return json_decode($response);
    }
    
    public function deleteCustomerByExternalId($externalId)
    {
        $this->_helper()->log(__METHOD__);
        try {
            $result = $this->findCustomersByExternalId($externalId);
            foreach ($result as $customer) {
                $this->deleteCustomer($customer->id);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    public function deleteCustomer($id)
    {
        $this->_helper()->log(__METHOD__);
        try {
            $response = $this->curlPost($this->_getApiUrl('customers/'.$id), null, true, null, 'DELETE');
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    public function createEvent($data)
    {
        $this->_helper()->log(__METHOD__);
        try {
            $response = $this->curlPost($this->_getApiUrl('events'), json_encode($data), true);
            
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    public function getAllCustomers($outputAssoc = false)
    {
        $result = null;
        try {
            $response = $this->curlGet($this->_getApiUrl('customers'), ['nodeId' => $this->_getNodeId(), 'size' => 20]);
            $response = json_decode($response, true);
            //return $response;
            if (!$response) {
                throw new \Exception('empty response', 664);
            }
            if (!isset($response['_embedded']['customers']) || !is_array($response['_embedded']['customers'])) {
                throw new \Exception('not valid response', 663);
            }
            $result = $response['_embedded']['customers'];
        } catch (\Exception $e) {
            throw $e;
        }
        //if (count($result) > 0) {
        return $result;
        //}
        //return null;
    }
     
    private function _helper()
    {
        if ($this->_helper == null) {
            $this->_helper = Mage::helper('contactlab_hub');
        }
        return $this->_helper;
    }
    
    private function _getApiUrl($actionUrl)
    {
        $apiUrl = $this->_helper()->getConfigData('settings/apiurl', $this->getStoreId());
        if (substr($apiUrl, -1) != '/') {
            $apiUrl .= '/';
        }
        
        //return $this->_apiUrl.$this->_apiVersion.$this->_apiWorkspace.'/'.$actionUrl;
        return $apiUrl.self::API_VERSION.$this->_getApiWorkspace().'/'.$actionUrl;
    }
    
    private function curlPost($url, $data = null, $authNeeded = true, $customHeader = null, $customRequest = null)
    {
        $this->_helper()->log(__METHOD__);
        $this->_helper()->log("CURL URL:");
        $this->_helper()->log($url);
        $this->_helper()->log("FINE CURL URL:");
        
        $curl = curl_init();
        $this->_helper()->log($url);
    
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($this->_getApiProxy()) {
            curl_setopt($curl, CURLOPT_PROXY, $this->_getApiProxy());
        }
        $header = array(
                'X-Forwarded-Ssl:on',
                'Content-Type:application/json'
        );
        if ($authNeeded) {
            $header[] = 'Authorization: Bearer ' . $this->_getApiToken();
        }
        if (is_array($customHeader)) {
            $header = array_merge($header, $customHeader);
        }
        if ($header) {
            //$this->_helper()->log(print_r($header, true));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        
        $this->_helper()->log("POST:");
        $this->_helper()->log(json_decode($data));
        $this->_helper()->log("FINE POST:");
                
        curl_setopt($curl, CURLOPT_POST, true);
        if (!is_null($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if (!is_null($customRequest)) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $customRequest);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        //        $this->_helper()->log('HTTP CODE:'.curl_getinfo($curl, CURLINFO_HTTP_CODE));
                
        $this->_helper()->log("RESPONSE:");
        $this->_helper()->log(json_decode($response));
        $this->_helper()->log("FINE RESPONSE:");
        
        if (curl_errno($curl) || $response === false) {
            $response = curl_error($curl);
            curl_close($curl);
            throw new \Exception($response, 667);
        }
        
        $this->_helper()->log("CURL HTTP CODE:");
        $this->_helper()->log(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        $this->_helper()->log("FINE CURL HTTP CODE:");
        
        $response = json_decode($response) ?: new \stdClass(); 
        $response->curl_http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = json_encode($response);
        
        //FIXME La post su session in realtà non torna un vero e proprio errore ma una notice...
        $tmp = explode('/', $url);
        if ($tmp[count($tmp)-1] != 'sessions') {
            if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 400) {
                $response = json_decode($response);
                if (property_exists($response, 'message')) {
                    $message = $response->message;
                } else {
                    $message = 'Something wrong.';
                }
                curl_close($curl);
                throw new \Exception($message, 668);
            }
        }
        curl_close($curl);
        //$this->_helper()->log('Curl report: '.print_r(curl_error($curl)));
        return $response;
    }
    
    private function curlGet($url, $query = null)
    {
        $this->_helper()->log(__METHOD__);
        $curl = curl_init();
        if ($query) {
            $url .= '?'.http_build_query($query);
        }
        $this->_helper()->log($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($this->_getApiProxy()) {
            curl_setopt($curl, CURLOPT_PROXY, $this->_getApiProxy());
        }
        
        if ($this->_getApiToken()) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'X-Forwarded-Ssl:on',
                     'Authorization: Bearer '.$this->_getApiToken(),
                    'Content-Type:application/json'
            ));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        if ($response === false) {
            $response = curl_error($curl);
            curl_close($curl);
            throw new \Exception($response, 666);
        }
        curl_close($curl);
        return $response;
    }
}
