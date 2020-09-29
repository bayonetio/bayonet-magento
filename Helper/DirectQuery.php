<?php

namespace Bayonet\BayonetAntiFraud\Helper;

use \Magento\Framework\App\ResourceConnection;

/**
 * Helper class to get specific values from the database using direct queries
 */
class DirectQuery
{
    protected $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Performs a direct query to the database to retrieve the requested
     * configuration value of the module
     * 
     * @param string $table
     * @param string $requestedValue
     * @param string $configPath
     * @return int
     */
    protected function configQuery($table, $requestedValue, $configPath)
    {
        $connection  = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName($table);
        $query = $connection->select()->from($tableName, $requestedValue)->where('path = :path');
        $path = 'bayonetantifraud_general/general/'.$configPath;
        $bind = [':path' => (string)$path];
        $result = $connection->fetchOne($query, $bind);

        return $result;
    }

    /**
     * Performs a direct query to the database to retrieve the requested
     * value of an order
     * 
     * @param string $table
     * @param string $requestedValue
     * @param string $orderId
     * @return string
     */
    public function orderQuery($table, $requestedValue, $orderId)
    {
        $connection  = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName($table);
        $query = $connection->select()->from($tableName, $requestedValue)->where('order_id = :orderId');
        $bind = [':orderId' => $orderId];
        $result = $connection->fetchOne($query, $bind);

        return $result;
    }

    /**
     * Performs a direct query to the database to retrieve the requested
     * value of a customer
     * 
     * @param string $table
     * @param string $requestedValue
     * @param string $customerId
     * @return string
     */
    protected function customerQuery($table, $requestedValue, $customerId)
    {
        $connection  = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName($table);
        $query = $connection->select()->from($tableName, $requestedValue)->where('customer_id = :customerId');
        $bind = [':customerId' => $customerId];
        $result = $connection->fetchOne($query, $bind);

        return $result;
    }
    
    /**
     * Gets the current value of the enable configuration for the module
     * 
     * @return int
     */
    public function getEnabled()
    {
        $enabled = $this->configQuery('core_config_data', 'value', 'enable');

        return intval($enabled);
    }

    /**
     * Gets the current value for the API mode
     * 
     * @return int
     */
    public function getApiMode()
    {
        $apiMode = $this->configQuery('core_config_data', 'value', 'api_mode');

        return intval($apiMode);
    }

    /**
     * Gets the current value for the specified API key
     * 
     * @return string
     */
    public function getApiKey($keyPath)
    {
        $apiKey = $this->configQuery('core_config_data', 'value', $keyPath);

        return $apiKey;
    }

    /**
     * Gets the Bayonet Tracking ID of an order in the Bayonet Orders table (if exists)
     * 
     * @return string
     */
    public function getTrackingId($orderId)
    {
        $trackingId = $this->orderQuery('bayonet_antifraud_orders', 'bayonet_tracking_id', $orderId);

        return $trackingId;
    }

    /**
     * Gets the API response of an order in the Bayonet Orders table (if exists)
     * 
     * @return string
     */
    public function getApiResponse($orderId)
    {
        $apiResponse = $this->orderQuery('bayonet_antifraud_orders', 'consulting_api_response', $orderId);

        return $apiResponse;
    }

    /**
     * Gets the Decision of an order in the Bayonet Orders table (if exists)
     * 
     * @return string
     */
    public function getDecision($orderId)
    {
        $decision = $this->orderQuery('bayonet_antifraud_orders', 'decision', $orderId);

        return $decision;
    }

    /**
     * Gets the triggered rules of an order in the Bayonet Orders table (if exist)
     * 
     * @return string
     */
    public function getRules($orderId)
    {
        $rules = $this->orderQuery('bayonet_antifraud_orders', 'triggered_rules', $orderId);

        return $rules;
    }

    public function getFingerprintToken($customerId)
    {
        $finrgerprintToken = $this->customerQuery('bayonet_antifraud_fingerprint', 'fingerprint_token', $customerId);

        return $finrgerprintToken;
    }
}
