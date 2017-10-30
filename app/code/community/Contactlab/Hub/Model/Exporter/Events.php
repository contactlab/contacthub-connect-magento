<?php
class Contactlab_Hub_Model_Exporter_Events extends Contactlab_Hubcommons_Model_Exporter_Abstract
{
	const PARTIAL_EXPORT	= 'partial';
	const FULL_EXPORT 		= 'full';
	
	protected $_mode;
	protected $_helper;	
	protected $_connectionWrite;
	protected $_connectionRead;
	protected $_tranche;
	protected $_trancheLimit;		
	protected $_folderName;
	protected $_filename;
	protected $_delimiter;
	
	
	private function _helper() {
		if ($this->_helper == null) {
			$this->_helper = Mage::helper('contactlab_hub');
		}
		return $this->_helper;
	}
	
	private function _getConfig($key) {
		return $this->_helper()->getConfigData($key);
	}
	
	
	/** Write xml object. */
	protected function writeXml()
	{
		return $this;
	}
	
	/**
	 * Is enabled.
	 * @return bool
	 */
	protected function isEnabled() 
	{	
		return true;
	}
	
	protected function getFileName() {
		return $this;
	}
	
	
	//public function export(Contactlab_Hubcommons_Model_Task_Interface $task)
	public function export()
	{	
		$pageSize = 30;
		$events = Mage::getModel('contactlab_hub/event')->getUnexportedEvents($pageSize);
		foreach($events as $event)
		{
			$event->export();			
		}
		return "Export done";
	}
	
		
	/** Put file into sftp or localhost. */
	private function _putFile($filename, $realFilename) {
		$sftp = new Contactlab_Hubcommons_Model_Ssh_Net_SFTP(
				$this->getTask()->getConfig("contactlab_hubcommons/connection/remote_server"));
		if (!$sftp->login(
				$this->getTask()->getConfig("contactlab_hubcommons/connection/sftp_username"),
				$this->getTask()->getConfig("contactlab_hubcommons/connection/sftp_password"))) {
					throw new Zend_Exception('Login Failed');
				}
	
				$sftp->put($realFilename, $filename, NET_SFTP_LOCAL_FILE);
				$this->_checkUploadedFile($filename, $realFilename, $sftp);
	
				$sftp->_disconnect(0);
	}
	
	/** Check uploaded file existence. */
	private function _checkUploadedFile($localFile, $remoteFile, $sftp) {
		$localFileSize = filesize($localFile);
		$remoteStat = $sftp->lstat($remoteFile);
		if (!$remoteStat) {
			throw new Zend_Exception(sprintf('There\'s been a problem during file upload: uploaded file %s not found', $remoteFile));
		}
		$this->getTask()->addEvent("Remote file info: " . print_r($remoteStat, true));
		$remoteFileSize = $remoteStat['size'];
		if ($localFileSize != $remoteFileSize) {
			throw new Zend_Exception(sprintf(
					'There\'s been a problem during file upload: original (%s) file\'s lenght is %d while uploaded '
					. '(%s) file\'s lenght is %d!', $localFile, $localFileSize, $remoteFile, $remoteFileSize));
		}
	}
}	
