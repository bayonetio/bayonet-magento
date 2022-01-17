<?php

namespace Bayonet\BayonetAntiFraud\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class InstallConfigData implements DataPatchInterface
{
    private $moduleDataSetup;
    private $resourceConfig;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ConfigInterface $resourceConfig
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resourceConfig = $resourceConfig;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $this->resourceConfig->saveConfig(
            'bayonetantifraud_general/general/api_version',
            'v2',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        )->saveConfig(
            'bayonetantifraud_general/general/enable',
            0,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        )->saveConfig(
            'bayonetantifraud_general/general/api_mode',
            1,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        $this->moduleDataSetup->endSetup();
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
