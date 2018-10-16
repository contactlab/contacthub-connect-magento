<?php
class Contactlab_Hub_Adminhtml_Contactlab_Hub_LogsController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('contactlab_hub/adminhtml_logs', 'hub_logs'));
        $this->renderLayout();
    }

    public function exportCsvAction()
    {
        $fileName = 'Event_export.csv';
        $content = $this->getLayout()->createBlock('contactlab_hub/adminhtml_logs_grid')->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportExcelAction()
    {
        $fileName = 'Event_export.xml';
        $content = $this->getLayout()->createBlock('contactlab_hub/adminhtml_logs_grid')->getExcelFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('ids');
        if (!is_array($ids)) {
            $this->_getSession()->addError($this->__('Please select Event(s).'));
        } else {
            try {
                foreach ($ids as $id) {
                    $model = Mage::getSingleton('contactlab_hub/event')->load($id);
                    $model->delete();
                }

                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) have been deleted.', count($ids))
                );
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('contactlab_hub')->__('An error occurred while mass deleting items. Please review log and try again.')
                );
                Mage::logException($e);
                return;
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Is this controller allowed?
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
