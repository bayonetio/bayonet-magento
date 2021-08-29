<?php

namespace Bayonet\BayonetAntiFraud\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\Api\FilterBuilder;
use \Magento\Framework\Api\Search\FilterGroupBuilder;
use \Magento\Framework\Api\SortOrder;
use \Magento\Framework\App\State;
use \Bayonet\BayonetAntiFraud\Helper\RequestHelper;
use \Bayonet\BayonetAntiFraud\Helper\GetData;
use \Bayonet\BayonetAntiFraud\Helper\SetData;
use \Bayonet\BayonetAntiFraud\Model\BayonetBackfillFactory;
use \Bayonet\BayonetAntiFraud\Helper\Order\OrderHelper;
use \Bayonet\BayonetAntiFraud\Helper\DirectQuery;

/**
 * Controller class for the backfill process command
 */
class Backfill extends Command
{
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
    protected $state;

    public function __construct(
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
        DirectQuery $directQuery,
        State $state
    ) {
        parent::__construct();
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
        $this->state = $state;
    }
    
    protected function configure()
    {
        $this->setName('bayonetantifraud:backfill'); // the command name when entering 'bin/magento list' in the terminal
        $this->setDescription('Performs the historical backfill for the Bayonet Anti-Fraud module'); // the command description
        
        parent::configure();
    }
    
    /**
     * Checks if the process has to be started or resumed, if it hasn't been
     * completed yet
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $bayonetBackfill = $this->bayonetBackfillFactory->create();
        $backfillData = $bayonetBackfill->getCollection();
        $backfillStatus = 0;
        if ($backfillData->getSize() > 0) {
            $backfillStatus = $backfillData->getFirstItem()->getData('backfill_status');
            if ((int)$backfillStatus === 1) {
                $output->writeln('Backfill process already completed');
            } else {
                $output->writeln('Backfill process started');
                $this->executeBackfill($input, $output);
            }
        } else {
            $output->writeln('Backfill process started');
            $this->executeBackfill($input, $output);
        }
    }

   /**
    * Executes the backfill process
    *
    * @param InputInterface $input
    * @param OutputInterface $output
    */
    protected function executeBackfill(InputInterface $input, OutputInterface $output)
    {
        $orders;
        $bayonetBackfill = $this->bayonetBackfillFactory->create();
        $backfillData = $bayonetBackfill->getCollection();
        $liveKey = $this->getHelper->getConfigValue('bayonet_live_key');

        if (!$liveKey) {
            $output->writeln("There is no API key to perform this process. You must save your API key before executing this process");
            return;
        }

        if ($backfillData->getSize() < 1) {  // checks if this is the first execution
            $orders = $this->getAllOrders(0, 0, 0);
            $dataToInsert = [
                'last_backfill_order' => $orders->getLastItem()->getId(),
                'total_orders' => $orders->getSize()
            ];
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
            if ((int)$this->directQuery->getConfigValue('bayonetantifraud_general/general/api_mode') === 1) { // checks if the API mode has not been changed to sandbox
                $requestBody = $this->prepareRequestBody($order);
                $response = $this->requestHelper->feedbackHistorical($requestBody);
                $dataToUpdate = [
                    'backfill_id' => $backfillData->getFirstItem()->getData('backfill_id'),
                    'processed_orders' => (int)$backfillData->getFirstItem()->getData('processed_orders') + 1,
                    'last_processed_order' => $order->getId()
                ];
                $bayonetBackfill->setData($dataToUpdate);
                $bayonetBackfill->save();
                $backfillData = $bayonetBackfill->getCollection();
            } else {
                $output->writeln("API Mode is currently set to testing, exiting process.");
                return;
            }
            $orderId = $order->getId();
            $output->writeln(
                "Response code for the order $orderId is: $response->reason_code with message $response->reason_message"
            );
        }
        $dataToUpdate = [
            'backfill_id' => $backfillData->getFirstItem()->getData('backfill_id'),
            'backfill_status' => 1
        ];
        $bayonetBackfill->setData($dataToUpdate);
        $bayonetBackfill->save();
        $output->writeln("Backfill process has been completed");
    }

    /**
     * Gets the orders collection.
     * Depending on the execution mode provided (0 for new process & 1 for continuation)
     * the function will either get all the orders or just the orders between a
     * specified range.
     *
     * @param int $executionMode
     * @param int $from
     * @param int $to
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    protected function getAllOrders($executionMode, $from, $to)
    {
        $orderSearcher;
        $bayonetOrders = $this->directQuery->getBayonetIds();
        $filtersToAdd = [];
        
        if ($executionMode === 1) { // if the process is being resumed (gets only remaining orders)
            if ($bayonetOrders) {
                $filterNin = $this->filterBuilder
                    ->setField('entity_id')
                    ->setConditionType('nin')
                    ->setValue($bayonetOrders)
                    ->create();
            
                $filterGroupNin = $this->filterGroupBuilder
                    ->addFilter($filterNin)
                    ->create();

                    array_push($filtersToAdd, $filterGroupNin);
            }
            
            $filterFrom = $this->filterBuilder
                ->setField('entity_id')
                ->setConditionType('gt')
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
            
            array_push($filtersToAdd, $filterGroupFrom, $filterGroupTo);

            $orderSearcher = $this->searchCriteriaBuilder
                ->setFilterGroups($filtersToAdd)
                ->addSortOrder($this->sortOrder->setDirection('ASC')->setField('entity_id'))
                ->create();
        } else { // if the process has been started for the first time (gets all orders)
            if ($bayonetOrders) {
                $filterNin = $this->filterBuilder
                    ->setField('entity_id')
                    ->setConditionType('nin')
                    ->setValue($bayonetOrders)
                    ->create();
            
                $filterGroupNin = $this->filterGroupBuilder
                    ->addFilter($filterNin)
                    ->create();

                array_push($filtersToAdd, $filterGroupNin);
            }

            $orderSearcher = $this->searchCriteriaBuilder
                ->setFilterGroups($filtersToAdd)
                ->addSortOrder($this->sortOrder->setDirection('ASC')->setField('entity_id'))
                ->create();
        }
        $ordersResult = $this->orderRepository->getList($orderSearcher);

        return $ordersResult;
    }

    /**
     * Generates the request body for an order object
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function prepareRequestBody($order)
    {
        $this->orderHelper->setOrder($order);
        $requestBody = $this->orderHelper->generateRequestBody('backfill');
        $apiKey = $this->getHelper->getConfigValue('bayonet_live_key');
        $requestBody['auth']['api_key'] = $apiKey;

        return $requestBody;
    }
}
