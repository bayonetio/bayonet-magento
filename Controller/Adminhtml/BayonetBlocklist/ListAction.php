<?php

namespace Bayonet\BayonetAntiFraud\Controller\Adminhtml\BayonetBlocklist;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Bayonet\BayonetAntiFraud\Model\BayonetBlocklistFactory;
use \Bayonet\BayonetAntiFraud\Helper\GetData;
use \Bayonet\BayonetAntiFraud\Helper\RequestHelper;
use \Magento\Customer\Model\CustomerFactory;

/**
 * Controller class for the blocklist/whitelist custom action column
 */
class ListAction extends Action
{
    protected $bayonetBlocklistFactory;
    protected $getHelper;
    protected $requestHelper;
    protected $customerFactory;

    public function __construct(
        Context $context,
        BayonetBlocklistFactory $bayonetBlocklistFactory,
        GetData $getHelper,
        RequestHelper $requestHelper,
        CustomerFactory $customerFactory
    ) {
        parent::__construct($context);
        $this->bayonetBlocklistFactory = $bayonetBlocklistFactory;
        $this->getHelper = $getHelper;
        $this->requestHelper = $requestHelper;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Executes the addition/removal of the customer requested
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $customerModel = $this->customerFactory->create();
        $blocklistId = $this->getRequest()->getParam('blocklist_id');
        $customerId = $this->getRequest()->getParam('customer_id');
        $customer = $customerModel->load($customerId);
        $customerEmail = $customer->getEmail();
        $whitelistCurrent = $this->getRequest()->getParam('whitelistValue');
        $blocklistCurrent = $this->getRequest()->getParam('blocklistValue');
        $listToManage = $this->getRequest()->getParam('list');
        $actionToPerform = $this->getRequest()->getParam('action');
        $apiMode = $this->getRequest()->getParam('api_mode');
        $apiKey = intval($apiMode) === 1 ? $this->getHelper->getConfigValue('bayonet_live_key') : $this->getHelper->getConfigValue('bayonet_sandbox_key');

        if (isset($apiKey) && $apiKey !== '') {
            $requestBody = [
                'auth' => ['api_key' => $apiKey],
                'email' => $customerEmail
            ];
            if ($blocklistId) {
                if ($listToManage === 'blocklist') {
                    if (intval($actionToPerform) === 1) {
                        $resultWhite = $this->removeWhite($requestBody);
                        if (isset($resultWhite) && intval($resultWhite->reason_code) === 0) {
                            $this->updateListRow($blocklistId, 0, $blocklistCurrent, $resultWhite->reason_code, $resultWhite->reason_message);
                            $resultBlock = $this->addBlock($requestBody);
                            if (isset($resultBlock) && intval($resultBlock->reason_code) === 0) {
                                $this->updateListRow($blocklistId, 0, 1, $resultBlock->reason_code, $resultBlock->reason_message);
                                $this->messageManager->addSuccess(__('Customer added to blocklist'));
                                return $resultRedirect->setPath('*/*/');
                            } else {
                                $this->messageManager->addError(__('An error ocurred while trying to add to the blocklist. Please try again'));
                                return $resultRedirect->setPath('*/*/');
                            }
                        } else {
                            $this->messageManager->addError(__('An error ocurred while trying to add to the blocklist. Please try again'));
                            return $resultRedirect->setPath('*/*/');
                        }
                    } else {
                        $resultBlock = $this->removeBlock($requestBody);
                        if (isset($resultBlock) && intval($resultBlock->reason_code) === 0) {
                            $this->updateListRow($blocklistId, $whitelistCurrent, 0, $resultBlock->reason_code, $resultBlock->reason_message);
                            $this->messageManager->addSuccess(__('Customer removed from blocklist'));
                            return $resultRedirect->setPath('*/*/');
                        } else {
                            $this->messageManager->addError(__('An error ocurred while trying to remove from the blocklist. Please try again'));
                            return $resultRedirect->setPath('*/*/');
                        }
                    }
                } else if ($listToManage === 'whitelist') {
                    if (intval($actionToPerform) === 1) {
                        $resultBlock = $this->removeBlock($requestBody);
                        if (isset($resultBlock) && intval($resultBlock->reason_code) === 0) {
                            $this->updateListRow($blocklistId, $whitelistCurrent, 0, $resultBlock->reason_code, $resultBlock->reason_message);
                            $resultWhite = $this->addWhite($requestBody);
                            if (isset($resultWhite) && intval($resultWhite->reason_code) === 0) {
                                $this->messageManager->addSuccess(__('Customer added to whitelist'));
                                $this->updateListRow($blocklistId, 1, 0, $resultWhite->reason_code, $resultWhite->reason_message);
                                return $resultRedirect->setPath('*/*/');
                            } else {
                                $this->messageManager->addError(__('An error ocurred while trying to add to the whitelist. Please try again'));
                                return $resultRedirect->setPath('*/*/');
                            }
                        } else {
                            $this->messageManager->addError(__('An error ocurred while trying to add to the whitelist. Please try again'));
                            return $resultRedirect->setPath('*/*/');
                        }
                    } else {
                        $resultWhite = $this->removeWhite($requestBody);
                        if (isset($resultWhite) && intval($resultWhite->reason_code) === 0) {
                            $this->updateListRow($blocklistId, 0, $blocklistCurrent, $resultWhite->reason_code, $resultWhite->reason_message);
                            $this->messageManager->addSuccess(__('Customer removed from whitelist'));
                            return $resultRedirect->setPath('*/*/');
                        } else {
                            $this->messageManager->addError(__('An error ocurred while trying to remove from the whitelist. Please try again'));
                            return $resultRedirect->setPath('*/*/');
                        }
                    }
                }
            } else {
                return $resultRedirect->setPath('*/*/');
            }
        } else {
            $this->messageManager->addError(__('The API key for that API mode has not been added yet. Please add your key and try again'));
            return $resultRedirect->setPath('*/*/');
        }
    }

    /**
     * Updates the corresponding blocklist table row with the provided data
     * 
     * @param int $blocklistId
     * @param int $whitelistValue
     * @param int $blocklistValue
     * @param int $responseCode
     * @param string $responseMessage
     */
    private function updateListRow($blocklistId, $whitelistValue, $blocklistValue, $responseCode, $responseMessage)
    {
        try {
            $listModel = $this->bayonetBlocklistFactory->create();
            $data = array(
                'blocklist_id' => $blocklistId,
                'whitelist' => $whitelistValue,
                'blocklist' => $blocklistValue,
                'response_code' => $responseCode,
                'response_message' => $responseMessage
            );
            $listModel->setData($data);
            $listModel->save();
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $resultRedirect->setPath('*/*/');
        }
    }

    /**
     * Calls the addBlocklist endpoint function in the request helper
     * 
     * @param array $requestBody
     * @return array
     */
    private function addBlock($requestBody)
    {
        $response = $this->requestHelper->addBlocklist($requestBody);

        return $response;
    }

    /**
     * Calls the removeBlocklist endpoint function in the request helper
     * 
     * @param array $requestBody
     * @return array
     */
    private function removeBlock($requestBody)
    {
        $response = $this->requestHelper->removeBlocklist($requestBody);

        return $response;
    }

    /**
     * Calls the addWhitelist endpoint function in the request helper
     * 
     * @param array $requestBody
     * @return array
     */
    private function addWhite($requestBody)
    {
        $response = $this->requestHelper->addWhitelist($requestBody);

        return $response;
    }

    /**
     * Calls the removeWhitelist endpoint function in the request helper
     * 
     * @param array $requestBody
     * @return array
     */
    private function removeWhite($requestBody)
    {
        $response = $this->requestHelper->removeWhitelist($requestBody);

        return $response;
    }
}
