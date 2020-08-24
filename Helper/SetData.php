<?php

namespace Bayonet\BayonetAntiFraud\Helper;

use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Helper class to set configuration values in the database.
 */
class SetData
{
    const XML_PATH_BAYONETANTIFRAUD = 'bayonetantifraud_general/';
    protected $configWriter;
    protected $cacheTypeList;
    protected $cacheFrontendPool;

    public function __construct(
        WriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
    }

    /**
     * Sets the specified configuration field with the provided value
     */
    public function setConfigValue($field, $value)
    {
        $this->configWriter->save(
            self::XML_PATH_BAYONETANTIFRAUD .'general/'. $field,
            $value,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId = 0
        );
    }
}
