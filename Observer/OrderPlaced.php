<?php

namespace Bayonet\BayonetAntiFraud\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Bayonet\BayonetAntiFraud\Helper\RequestHelper;
use \Bayonet\BayonetAntiFraud\Helper\GetData;
use \Bayonet\BayonetAntiFraud\Model\BayonetOrderFactory;
use \Bayonet\BayonetAntiFraud\Model\BayonetBlocklistFactory;
use \Bayonet\BayonetAntiFraud\Helper\Order\OrderHelper;
use \Bayonet\BayonetAntiFraud\Helper\DirectQuery;

/**
 * Observer class for the order placement
 */
class OrderPlaced implements ObserverInterface
{
    protected $directQuery;
    protected $getHelper;
    protected $orderHelper;
    protected $requestHelper;
    protected $bayonetOrderFactory;
    protected $bayonetBlocklistFactory;

    public function __construct(
        DirectQuery $directQuery,
        GetData $getHelper,
        OrderHelper $orderHelper,
        RequestHelper $requestHelper,
        BayonetOrderFactory $bayonetOrderFactory,
        BayonetBlocklistFactory $bayonetBlocklistFactory
    ) {
        $this->directQuery = $directQuery;
        $this->getHelper = $getHelper;
        $this->orderHelper = $orderHelper;
        $this->requestHelper = $requestHelper;
        $this->bayonetOrderFactory = $bayonetOrderFactory;
        $this->bayonetBlocklistFactory = $bayonetBlocklistFactory;
    }

    /**
     * The main function of the class, this is where all the execution happens.
     * The function will check if the configuration values are set correctly in
     * order to proceed with the execution
     * The function creates a request body to then perform a consulting request
     * to Bayonet's API and, depending on the response, add a row to Bayonet's
     * orders table with the corresponding data
     * The function will also add the customer to the blocklist table in case
     * they haven't been added previously
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quoteId = $order->getQuoteId();
        $moduleEnabled = $this->getHelper->getConfigValue('enable');
        $apiMode = $this->getHelper->getConfigValue('api_mode');
        $sandboxKey = $this->getHelper->getConfigValue('bayonet_sandbox_key');
        $liveKey = $this->getHelper->getConfigValue('bayonet_live_key');
        
        if (!$moduleEnabled || (int)$moduleEnabled === 0) {
            return;
        }
        
        if ((!$sandboxKey && (int)$apiMode === 0) || (!$liveKey && (int)$apiMode === 1)) {
            return;
        }
        
        if (!$order) {
            return;
        }
        
        try {
            $this->orderHelper->setOrder($order);
            $requestBody = $this->orderHelper->generateRequestBody('consulting');
            $requestBody['auth']['api_key'] = (int)$apiMode === 1 ? $liveKey : $sandboxKey;
            $response = $this->requestHelper->consulting($requestBody);
            $bayonetOrder = $this->bayonetOrderFactory->create();
            $orderData = [
                'quote_id' => $requestBody['order_id'],
                'api_mode' => $apiMode
            ];

            if ($response) {
                $orderData['bayonet_tracking_id'] = (int)$response->reason_code === 0 ?
                    $response->bayonet_tracking_id :
                    null;
                $orderData['consulting_api'] = (int)$response->reason_code === 0 ? 1 : 0;
                $orderData['consulting_api_response'] = json_encode(
                    [
                        'reason_code' => $response->reason_code,
                        'reason_message' => $response->reason_message,
                    ]
                );
                $orderData['decision'] = (int)$response->reason_code === 0 ? $response->decision : null;
                $orderData['triggered_rules'] = (int)$response->reason_code === 0 ?
                    $this->getTriggeredRules($response) :
                    null;
                $orderData['executed'] = 1;
                $bayonetOrder->setData($orderData);
                $bayonetOrder->save();

                if (isset($response['decision']) && $response->decision === 'decline') {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        "There was an error processing your order. Please try again later"
                    ));
                }
            } else {
                $orderData['consulting_api'] = 0;
                $orderData['executed'] = 0;
                $bayonetOrder->setData($orderData);
                $bayonetOrder->save();
            }

            if ($response && (int)$requestBody['consumer_internal_id']) {
                $this->addBlocklistRows($requestBody['consumer_internal_id'], $requestBody['email']);
            }
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Gets the triggered rules of a successful consulting request to
     * Bayonet's API
     *
     * @param array $response
     * @return string
     */
    protected function getTriggeredRules($response)
    {
        $triggeredRules = '';
        $dynamicRules = $response->rules_triggered->dynamic;
        $customRules = $response->rules_triggered->custom;

        foreach ($dynamicRules as $rule) {
            $triggeredRules .= $rule . ', ';
        }

        foreach ($customRules as $rule) {
            $triggeredRules .= $rule . ', ';
        }

        $triggeredRules = substr($triggeredRules, 0, -2);

        return $triggeredRules;
    }

    /**
     * Adds a customer to the Bayonet's blocklist table in the database.
     * It performs a validation before trying to add them, this to make
     * sure the customer is not present in the table yet
     *
     * @param string $customerId
     * @param string $email
     */
    protected function addBlocklistRows($customerId, $email)
    {
        $bayonetBlocklist = $this->bayonetBlocklistFactory->create();
        $blocklistRow = $bayonetBlocklist->load($customerId, 'customer_id');

        if (empty($blocklistRow->getData())) {
            $blocklistData = [
                'customer_id' => $customerId,
                'email' => $email,
                'api_mode' => 0
            ];

            $bayonetBlocklist->setData($blocklistData);
            $bayonetBlocklist->save();
            $blocklistData['api_mode'] = 1; // add row for live API mode
            $bayonetBlocklist->setData($blocklistData);
            $bayonetBlocklist->save();
        }
    }
}
