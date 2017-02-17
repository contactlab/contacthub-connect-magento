<?php 
class Contactlab_Hub_Model_Hub extends Mage_Core_Model_Abstract 
{
	protected $_helper = null;
	protected $_apiUrl = null;
	protected $_apiVersion = 'hub/v1/workspaces/';
	protected $_apiWorkspace = null;
	protected $_apiNodeId = null;
	protected $_apiContext = null;
	protected $_apiToken = null;
	protected $_apiProxy = null;
	
	private function _helper()
	{
		if ($this->_helper == null) {
			$this->_helper = Mage::helper('contactlab_hub');
		}
		return $this->_helper;
	}
		
	public function __construct()
	{		
		$this->_apiUrl = $this->_helper()->getConfigData('settings/apiurl');		
		$this->_apiToken = $this->_helper()->getConfigData('settings/apitoken');
		$this->_apiWorkspace = $this->_helper()->getConfigData('settings/apiworkspaceid');
		$this->_apiNodeId = $this->_helper()->getConfigData('settings/apinodeid');
		$this->_apiContext = $this->_helper()->getConfigData('settings/apicontext');
		$this->_apiProxy = $this->_helper()->getConfigData('settings/useproxy') ? $this->_helper()->getConfigData('settings/apiproxy') : false;		
			
		if (substr($this->_apiUrl, -1) != '/') 
		{
			$this->_apiUrl .= '/';
		}		
	}
	
	public function getRemoteCustomerHub($data)
	{
		$this->_helper()->log(__METHOD__);
		try {
			$response = $this->curlPost($this->_getApiUrl('customers'), json_encode($data), true);
			$response = json_decode($response);
			if ($response->curl_http_code == 409) 
			{							
				unset($data->nodeId);
				$data->id = $response->data->customer->id;
				//unset($data->subscriptions);
				$response = $this->curlPost($response->data->customer->href, json_encode($data), true, null, "PATCH");
				return json_decode($response);
			}			
			else 
			{
				json_decode($response);
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
			$response = $this->curlGet($this->_getApiUrl('customers'), ['nodeId' => $this->_apiNodeId, 'size' => 20]);
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
	
	private function renewToken() 
	{
		if (!$this->_apiToken) {
			return $this->getToken();
		}
		return true;
	}
	
	private function getToken() 
	{
	
		return true;
	}
	
	private function _getApiUrl($actionUrl) 
	{
		return $this->_apiUrl.$this->_apiVersion.$this->_apiWorkspace.'/'.$actionUrl;
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
		if (!empty($this->getApiProxy())) {
			curl_setopt($curl, CURLOPT_PROXY, $this->getApiProxy());
		}
		$header = array(
				'X-Forwarded-Ssl:on',
				'Content-Type:application/json'
		);
		if ($authNeeded) {		
			$header[] = 'Authorization: Bearer ' . $this->_apiToken;
		}
		if (is_array($customHeader)) {
			$header = array_merge($header, $customHeader);
		}
		if (!empty($header)) {
			//$this->_helper()->log(print_r($header, true));			
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		}
		
		$this->_helper()->log("POST:");
		$this->_helper()->log(json_decode($data));
		$this->_helper()->log("FINE POST:");
				
		curl_setopt($curl, CURLOPT_POST, TRUE);
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
		
		$response = json_decode($response);
		$response->curl_http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$response = json_encode($response);
		
		//FIXME La post su session in realtà non torna un vero e proprio errore ma una notice...
		$tmp = explode('/', $url);		
		if($tmp[count($tmp)-1] != 'sessions')
		{
			
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
	
	
	private function curlGet($url, $query = null) {
		$this->_helper()->log(__METHOD__);
		$curl = curl_init();
		if ($query) {
			$url .= '?'.http_build_query($query);
		}
		$this->_helper()->log($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		if (!empty($this->getApiProxy())) {
			curl_setopt($curl, CURLOPT_PROXY, $this->getApiProxy());
		}
		
		if ($this->_apiToken) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
					'X-Forwarded-Ssl:on',
					 'Authorization: Bearer '.$this->_apiToken,
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