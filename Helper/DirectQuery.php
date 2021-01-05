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
     * Performs a custom query to the Magento database.
     * Depending on the provided data, the query will be created and
     * then executed to retrieve the requested value.
     * If the where conditions include 'decision', it will be as not equals
     * to 'decline', this to retrieve only quotes/orders that were actually
     * processed and has the correct data; if a quote is declined in the
     * consulting call, a row is inserted to the database but it does not
     * have the necessary data for the update transaction call, in this way,
     * avoiding all the 'declined' rows will then retrieve the correct row
     *
     * @param string $table
     * @param string $requestedValue
     * @param array $whereConditions
     *
     * @return bool|string
     */
    public function customQuery($table, $requestedValue, $whereConditions)
    {
        $connection  = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName($table);
        $select = $connection->select()
            ->from(
                ['o' =>  $tableName],
                $requestedValue
            );
        
        if ($whereConditions) {
            foreach ($whereConditions as $key => $value) {
                if ($key === 'decision') {
                    $select = $select->where("o.$key != ? OR o.$key is NULL", $value);
                } else {
                    $select = $select->where("o.$key = ?", $value);
                }
            }
        }
        
        $result = $connection->fetchOne($select);

        return $result;
    }

    /**
     * Gets a requested configuration value from the database based on the
     * provided configuration path
     *
     * @param string $keyPath
     * @return string|int
     *
     */
    public function getConfigValue($keyPath)
    {
        $whereConditions = [
            'path' => $keyPath
        ];

        $configValue = $this->customQuery('core_config_data', 'value', $whereConditions);

        return $configValue;
    }

    /**
     * Gets the payment gateway of an order.
     * This direct query is performed due to Magento changing the payment
     * gateway of an order to 'substitution' whenever a gateway has been
     * removed from the store.
     * The payment gateway is still stored in the sales_order_payment table
     * though, thus, it is necessary to perform a query to this table to
     * retrieve the correct gateway when performing the backfill process
     *
     * @param int $orderId
     * @return string
     */
    public function getPaymentGateway($orderId)
    {
        $paymentGateway = $this->customQuery('sales_order_payment', 'method', ['parent_id' => $orderId]);

        return $paymentGateway;
    }

    /**
     * Gets the IDs of the orders already processed by Bayonet
     *
     * @return array
     */
    public function getBayonetIds()
    {
        $connection  = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('bayonet_antifraud_orders');
        $query = $connection->select('distinct')->from($tableName, 'order_id')->where('order_id is not null');
        $result = $connection->fetchCol($query);

        return $result;
    }
}
