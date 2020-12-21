<?php

namespace Bayonet\BayonetAntiFraud\Model\BayonetBlocklist\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ApiMode.
 * Used to map the API modes in the Bayonet Blocklist custom grid in the
 * Admin dashboard
 */
class ApiMode implements OptionSourceInterface
{
    protected $bayonetBlocklist;

    public function __construct(\Bayonet\BayonetAntiFraud\Model\BayonetBlocklist $bayonetBlocklist)
    {
        $this->bayonetBlocklist = $bayonetBlocklist;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->bayonetBlocklist->getAvailableApiModes();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
