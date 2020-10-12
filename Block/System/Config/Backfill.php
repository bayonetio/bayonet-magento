<?php

namespace Bayonet\BayonetAntiFraud\Block\System\Config;

use \Magento\Backend\Block\Template\Context;
use \Magento\Config\Block\System\Config\Form\Field;
use \Magento\Framework\Data\Form\Element\AbstractElement;
use \Bayonet\BayonetAntiFraud\Helper\GetData;
use \Bayonet\BayonetAntiFraud\Helper\DirectQuery;

/**
 * Class that defines the button for the backfill.
 */
class Backfill extends Field
{
    protected $_template = 'Bayonet_BayonetAntiFraud::system/config/backfill.phtml';
    protected $getHelper;
    protected $directQuery;

    public function __construct(
        Context $context,
        GetData $getHelper,
        DirectQuery $directQuery,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->getHelper = $getHelper;
        $this->directQuery = $directQuery;
    }

    /**
     * Removes scope label
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Returns element html
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Returns the URL that should be used in an ajax requestto access the
     * button's controller when is pressed.
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('bayonet_bayonetantifraud/system_config/backfill');
    }

    /**
     * Defines the appearance of the button and generates the html
     */
    public function getButtonHtml()
    {
        $apiMode = intval($this->getHelper->getConfigValue('api_mode'));
        $backfillMode = intval($this->directQuery->getBackfillMode());
        $disabledButton = $apiMode === 1 ? false : true;
        $buttonId = $backfillMode === 1 ? 'backfillStop' : 'backfillInitiate';
        $buttonLabel = $backfillMode === 1 ? 'Stop Backfill' : 'Initiate Backfill';
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
            )->setData(
                [
                    'id' => $buttonId,
                    'label' => __($buttonLabel),
                    'disabled' => $disabledButton
                ]
            );

        return $button->toHtml();
    }
}
