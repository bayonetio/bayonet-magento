<?php

namespace Bayonet\BayonetAntiFraud\Controller\Adminhtml\System\Config;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\Api\FilterBuilder;
use \Magento\Framework\Api\Search\FilterGroupBuilder;
use \Magento\Framework\Api\SortOrder;
use \Bayonet\BayonetAntiFraud\Api\RequestHelper;
use \Bayonet\BayonetAntiFraud\Helper\GetData;
use \Bayonet\BayonetAntiFraud\Helper\SetData;
use \Bayonet\BayonetAntiFraud\Model\BayonetBackfillFactory;
use \Bayonet\BayonetAntiFraud\Helper\Order\OrderHelper;
use \Bayonet\BayonetAntiFraud\Helper\DirectQuery;

/**
 * Controller class for the backfill button in the admin dashboard.
 */
class Backfill extends \Magento\Backend\App\Action
{
    protected $resultJsonFactory;
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $filterGroupBuilder;
    protected $sortOrder;
    protected $getHelper;
    protected $setHelper;
    protected $requestHelper;
    protected $bayonetBackfillFactory;
    protected $orderHelper;
    protected $directQuery;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SortOrder $sortOrder,
        GetData $getHelper,
        SetData $setHelper,
        RequestHelper $requestHelper,
        BayonetBackfillFactory $bayonetBackfillFactory,
        OrderHelper $orderHelper,
        DirectQuery $directQuery
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->sortOrder = $sortOrder;
        $this->getHelper = $getHelper;
        $this->setHelper = $setHelper;
        $this->requestHelper = $requestHelper;
        $this->bayonetBackfillFactory = $bayonetBackfillFactory;
        $this->orderHelper = $orderHelper;
        $this->directQuery = $directQuery;
    }

    /**
     * Function that is executed when an ajax request is performed after the
     * button is pressed. Decides what to do depending on the 'action' value
     * provided in the POST request.
     */
    public function execute()
    {
        $post = $this->getRequest()->getPost();
        if ($post['action'] === 'initiate') {
            return $this->startBackfill();
        } else if ($post['action'] === 'execute') {
            $this->executeBackfill();
        } else if ($post['action'] === 'stop') {
            return $this->stopBackfill();
        } else if ($post['action'] === 'status') {
            if (intval($this->directQuery->getBackfillMode()) === 1) {
                return $this->getStatus();
            } else {
                $result = 'error';
                $response = $this->resultJsonFactory->create();
                return $response->setData($result);
            }
        }
    }

    /**
     * Changes the backfill mode configuration value to 1, which means that the
     * backfill process has been started and is currently active.
     */
    protected function startBackfill()
    {
        $response = $this->resultJsonFactory->create();
        $this->setHelper->setConfigValue('backfill_mode', 1);
        if (intval($this->directQuery->getBackfillMode()) === 1) {
            $result = 'ok';
            return $response->setData($result);
        } else {
            $result = 'error';
            return $response->setData($result);
        }
    }

    /**
     * Executes the backfill process.
     */
    protected function executeBackfill()
    {
        $orders;
        $bayonetBackfill = $this->bayonetBackfillFactory->create();
        $backfillData = $bayonetBackfill->getCollection();
        if ($backfillData->getSize() < 1) {  // checks if this is the first execution
            $orders = $this->getAllOrders(0, 0, 0);
            $dataToInsert = array(
                'last_backfill_order' => $orders->getLastItem()->getId(),
                'total_orders' => $orders->getSize()
            );
            $bayonetBackfill->setData($dataToInsert);
            $bayonetBackfill->save();
            $bayonetBackfill = $this->bayonetBackfillFactory->create();
            $backfillData = $bayonetBackfill->getCollection();
        } else { // gets the backfill data if the process has been previously started & stopped without being finished
            $orders = $this->getAllOrders(
                1,
                $backfillData->getFirstItem()->getData('last_processed_order'),
                $backfillData->getFirstItem()->getData('last_backfill_order')
            );
        }
        foreach ($orders as $order) {
            if (intval($this->directQuery->getBackfillMode()) === 1) { // checks if the process has not been stopped by the merchant
                $requestBody = $this->generateRequestBody($order);
                $response = $this->requestHelper->feedbackHistorical($requestBody);
                $dataToUpdate = array(
                    'backfill_id' => $backfillData->getFirstItem()->getData('backfill_id'),
                    'processed_orders' => intval($backfillData->getFirstItem()->getData('processed_orders')) + 1,
                    'last_processed_order' => $order->getId()
                );
                $bayonetBackfill->setData($dataToUpdate);
                $bayonetBackfill->save();
                $backfillData = $bayonetBackfill->getCollection();
            } else {
                exit;
            }
        }
        $dataToUpdate = array(
            'backfill_id' => $backfillData->getFirstItem()->getData('backfill_id'),
            'backfill_status' => 1
        );
        $bayonetBackfill->setData($dataToUpdate);
        $bayonetBackfill->save();
        $this->setHelper->setConfigValue('backfill_mode', 0);
    }

    /**
     * Changes the backfill mode configuration value to 0, which means that the
     * backfill process has been stopped and is currently inactive.
     */
    protected function stopBackfill()
    {
        $response = $this->resultJsonFactory->create();
        $this->setHelper->setConfigValue('backfill_mode', 0);
        if (intval($this->directQuery->getBackfillMode()) !== 1) {
            $result = 'ok';
            return $response->setData($result);
        } else {
            $result = 'error';
            return $response->setData($result);
        }
    }

    /**
     * Gets the current status of the backfill process
     */
    protected function getStatus()
    {
        $data = [];
        $response = $this->resultJsonFactory->create();
        $bayonetBackfill = $this->bayonetBackfillFactory->create();
        $backfillData = $bayonetBackfill->getCollection();
        if ($backfillData->getSize() < 1) {
            $processedOrders = intval($backfillData->getFirstItem()->getData('processed_orders'));
            $totalOrders = intval($backfillData->getFirstItem()->getData('total_orders'));
            $percentage = ($processedOrders/$totalOrders)*100;
            $roundPct = ceil($percentage);
            $data['percentage'] = $roundPct;

            if ($roundPct <= 100) {
                $data['result'] = 'done';
                $this->setHelper->setConfigValue('backfill_mode', 0);
            } else {
                $data['result'] = 'running';
            }
            return $response->setData($data);
        }
    }

    /**
     * Gets the orders collection.
     * Depending on the execution mode provided (0 for new process & 1 for continuation)
     * the function will either get all the orders or just the orders between a
     * specified range.
     */
    protected function getAllOrders($executionMode, $from, $to)
    {
        $ordersSearcher;

        if ($executionMode === 1) {
            $filterFrom = $this->filterBuilder
                ->setField('entity_id')
                ->setConditionType('gteq')
                ->setValue($from)
                ->create();

            $filterGroupFrom = $this->filterGroupBuilder
                ->addFilter($filterFrom)
                ->create();

            $filterTo = $this->filterBuilder
                ->setField('entity_id')
                ->setConditionType('lteq')
                ->setValue($to)
                ->create();

            $filterGroupTo = $this->filterGroupBuilder
                ->addFilter($filterTo)
                ->create();

            $ordersSearcher = $this->searchCriteriaBuilder
                ->setFilterGroups([$filterGroupFrom, $filterGroupTo])
                ->addSortOrder($this->sortOrder->setDirection('ASC')->setField('entity_id'))
                ->create();
        } else {
            $ordersSearcher = $this->searchCriteriaBuilder
                ->addSortOrder($this->sortOrder->setDirection('ASC')->setField('entity_id'))
                ->create();
        }
        $ordersResult = $this->orderRepository->getList($ordersSearcher);

        return $ordersResult;
    }

    /**
     * Generates the request body for an order object
     */
    protected function generateRequestBody($order)
    {
        $this->orderHelper->setOrder($order);

        $requestBody = [
            'channel' => 'ecommerce',
            'consumer_internal_id' => $order->getCustomerId(),
            'consumer_name' => $this->orderHelper->getCustomerName(),
            'email' => $order->getCustomerEmail(),
            'telephone' => $order->getBillingAddress()->getTelephone(),
            'billing_address' => $this->orderHelper->getBillingAddress(),
            'shipping_address' => $this->orderHelper->getShippingAddress(),
            'products' => $this->orderHelper->getProducts(),
            'order_id' => $order->getId(),
            'currency_code' => $order->getOrderCurrencyCode(),
            'payment_gateway' => $order->getPayment()->getMethodInstance()->getCode(),
            'transaction_amount' => number_format((float)$order->getGrandTotal(), 1, '.', ''),
            'transaction_time' => strtotime($order->getCreatedAt()),
        ];

        $apiKey = $this->getHelper->getConfigValue('bayonet_live_key');
        $requestBody['auth']['api_key'] = $apiKey;

        return $requestBody;
    }
}
