<?php

class Contactlab_Hub_Block_Adminhtml_Widget_Button extends Mage_Adminhtml_Block_Widget_Button
{
    protected function _construct()
    {
        parent::_construct();
        $this->setParams("");
    }

    /**
     * Set url.
     * @param type $url
     * @param type $params
     */
    public function setUrl($url, $params = "") {
        parent::setButtonUrl($url);
        $this->setParams($params);
    }

    public function getOnClick() {
        $url = $this->getButtonUrl();
        $params = array();
        parse_str($this->getParams(), $params);
        if ($this->hasConfirm()) {
			$onclick = 'deleteConfirm(\'' . $this->getConfirm()
                . '\', \'' . Mage::helper('adminhtml')->getUrl($url, $params) . '\')';
		} else {
            $onclick = 'location.href = \'' . Mage::helper('adminhtml')->getUrl($url, $params) . '\'';
		}
        return $onclick;
    }
}
