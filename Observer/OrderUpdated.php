<?php

namespace Bayonet\BayonetAntiFraud\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Bayonet\BayonetAntiFraud\Helper\RequestHelper;
use \Bayonet\BayonetAntiFraud\Helper\GetData;
use \Bayonet\BayonetAntiFraud\Model\BayonetOrderFactory;
use \Bayonet\BayonetAntiFraud\Model\BayonetBlocklistFactory;
use \Bayonet\BayonetAntiFraud\Helper\Order\OrderHelper;
use \Bayonet\BayonetAntiFraud\Helper\DirectQuery;

/**
 * Observer class for the order update event
 * It will be executed everytime an order is updated but will only perform
 * actions when the order's state has been changed
 */
class OrderUpdated implements ObserverInterface
{
    protected $directQuery;
    protected $getHelper;
    protected $orderHelper;
    protected $requestHelper;
    protected $bayonetOrderFactory;
    protected $bayonetBlocklistFactory;
    protected $searchCriteriaBuilder;
    protected $orderRepository;

    public function __construct(
        DirectQuery $directQuery,
        GetData $getHelper,
        OrderHelper $orderHelper,
        RequestHelper $requestHelper,
        BayonetOrderFactory $bayonetOrderFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->directQuery = $directQuery;
        $this->getHelper = $getHelper;
        $this->orderHelper = $orderHelper;
        $this->requestHelper = $requestHelper;
        $this->bayonetOrderFactory = $bayonetOrderFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $apiMode = $this->getHelper->getConfigValue('api_mode');
        $liveKey = $this->getHelper->getConfigValue('bayonet_live_key');
        $sandboxKey = $this->getHelper->getConfigValue('bayonet_sandbox_key');

        if (!$order) {
            return;
        }

        $this->orderHelper->setOrder($order);
        $whereConditions = [
            'quote_id' => (int)$order->getData('quote_id'),
            'decision' => 'decline'
        ];

        try {
            $bayonetId = (int)$this->directQuery->customQuery(
                'bayonet_antifraud_orders',
                'bayonet_id',
                $whereConditions
            );
            $trackingId = $this->directQuery->customQuery(
                'bayonet_antifraud_orders',
                'bayonet_tracking_id',
                $whereConditions
            );

            if (!$bayonetId || !$trackingId) {
                return;
            }

            $currentState = $this->directQuery->customQuery(
                'bayonet_antifraud_orders',
                'current_state',
                $whereConditions
            );

            if ($currentState === $order->getState()) {
                return;
            }

            $requestBody = [
                'bayonet_tracking_id' => $trackingId,
                'transaction_status' => $this->orderHelper->getTransactionStatus()
            ];

            $requestBody['auth']['api_key'] = (int)$apiMode === 1 ? $liveKey : $sandboxKey;

            $response = $this->requestHelper->updateTransaction($requestBody);
            $bayonetOrder = $this->bayonetOrderFactory->create();
            $orderData = [
                'bayonet_id' => $bayonetId,
                'current_state' => $order->getState()
            ];

            if (!$currentState) {
                $orderData['order_id'] = $order->getId();
            }

            if ($response) {
                $orderData['feedback_api'] = 1;
                $orderData['feedback_api_response'] = json_encode(
                    [
                        'reason_code' => $response->reason_code,
                        'reason_message' => $response->reason_message,
                    ]
                );
            } else {
                $orderData['feedback_api'] = 0;
            }
            $bayonetOrder->setData($orderData);
            $bayonetOrder->save();
        } catch (\Exception $e) {
        }
    }
}
