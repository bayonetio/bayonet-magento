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
     * Gets the current value for the backfill mode
     */
    public function getBackfillMode()
    {
        $connection  = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('core_config_data');
        $query = $connection->select()->from($tableName, 'value')->where('path = :path');
        $path = 'bayonetantifraud_general/general/backfill_mode';
        $bind = [':path' => (string)$path];
        $result = (int)$connection->fetchOne($query, $bind);

        return $result;
    }
}
