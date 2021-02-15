<?php

namespace Bayonet\BayonetAntiFraud\Model\BayonetOrder\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Decision.
 * Used to map the decisions in the Bayonet Orders custom grid in the
 * Admin dashboard
 */
class Decision implements OptionSourceInterface
{
    protected $bayonetOrder;

    public function __construct(\Bayonet\BayonetAntiFraud\Model\BayonetOrder $bayonetOrder)
    {
        $this->bayonetOrder = $bayonetOrder;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->bayonetOrder->getAvailableDecisions();
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
