<?php

/**
 * Model for getSubscriberDataExchangeStatus calls.
 */
class Contactlab_Hubcommons_Model_Soap_GetSubscriberDataExchangeStatus extends Contactlab_Hubcommons_Model_Soap_AbstractCall {
	/** Do the SOAP call. */
	public function call() {
        if ($this->getConfigFlag('contactlab_hubcommons/soap/enable')) {
            return "DISABLED";
        }
		$this->validateStatus();
		require_once('getSubscriberDataExchangeStatus.php');
		$params = new getSubscriberDataExchangeStatus();
		$params->token = $this->getAuthToken();
        // FIXME multi store
        $params->dataExchangeConfigIdentifier = $this->getConfig("contactlab_hubcommons/soap/data_updater_config_identifier");
		
		$rv = $this->getClient()->getSubscriberDataExchangeStatus($params);
		return $rv->return;
	}
}
