<?php

namespace Bayonet\BayonetAntiFraud\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\ScopeInterface;

/**
 * Helper class to get configuration values from the database.
 */
class GetData extends AbstractHelper
{
    const XML_PATH_BAYONETANTIFRAUD = 'bayonetantifraud_general/';

    /**
     * Gets the value of the specified configuration field
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BAYONETANTIFRAUD .'general/'. $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
