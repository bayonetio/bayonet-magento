<?php

namespace Bayonet\BayonetAntiFraud\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class to define a blocklist action in the admin grid
 */
class BlocklistAction extends Column
{
    const CMS_URL_PATH_BLOCKLIST = 'bayonet_bayonetantifraud/bayonetblocklist/listaction';
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepares the data source and adds the corresponding action in a column
     * inside the admin grid. The action will add the current customer to
     * either the blocklist or whitelist
     * 
     * @param array $dataSource
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['blocklist_id'])) {
                    $columnName = $this->getData('config/label');
                    $actionLabel = '';
                    $actionToPerform = 0;
                    $listToManage = 'blocklist';
                    if (strpos($columnName, 'Block') !== false) {
                        $actionLabel = intval($item['blocklist']) === 1 ? 'Remove from Blocklist' : 'Add to Blocklist';
                        $actionToPerform = intval($item['blocklist']) === 1 ? 0 : 1;
                    } else if (strpos($columnName, 'White') !== false) {
                        $actionLabel = intval($item['whitelist']) === 1 ? 'Remove from Whitelist' : 'Add to Whitelist';
                        $actionToPerform = intval($item['whitelist']) === 1 ? 0 : 1;
                        $listToManage = 'whitelist';
                    }
                    $item[$this->getData('name')] = [
                        'listAction' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::CMS_URL_PATH_BLOCKLIST,
                                [
                                    'blocklist_id' => $item['blocklist_id'],
                                    'customer_id' => $item['customer_id'],
                                    'blocklistValue' => $item['blocklist'],
                                    'whitelistValue' => $item['whitelist'],
                                    'list' => $listToManage,
                                    'action' => $actionToPerform,
                                    'api_mode' => $item['api_mode']
                                ]
                            ),
                            'label' => __($actionLabel),
                            'confirm' => [
                                'title' => __('Customer with ID %1', $item['customer_id']),
                                'message' => __('Are you sure you want to '.strtolower($actionLabel).' customer with ID %1?', $item['customer_id'])
                            ],
                            'post' => true
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}
