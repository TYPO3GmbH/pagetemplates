<?php
declare(strict_types = 1);

namespace T3G\Pagetemplates\ContextMenu;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CreatePageFromTemplateItemProvider extends AbstractProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'createPageFromTemplate' => [
            'type' => 'item',
            'label' => 'LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_page_from_template',
            'iconIdentifier' => 'actions-document-new',
            'callbackAction' => 'createPageFromTemplate'
        ]
    ];

    /**
     * Returns the provider priority which is used for determining the order in which providers are adding items
     * to the result array. Highest priority means provider is evaluated first.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 40;
    }

    /**
     * Whether this provider can handle given request (usually a check based on table, uid and context)
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        $result = false;
        if ($this->table === 'pages') {
            $result = true;
        }
        return $result;
    }

    /**
     * Add "createPageFromTemplate" item
     *
     * @param array $items
     * @return array
     */
    public function addItems(array $items): array
    {
        $localItems = $this->prepareItems($this->itemsConfiguration);
        $items += $localItems;
        return $items;
    }

    /**
     * Load JS requireJS module
     *
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $referenceElementUid = GeneralUtility::trimExplode('-', $this->context);
        $result = [
            'data-callback-module' => 'TYPO3/CMS/Pagetemplates/ContextMenuAction',
            'data-reference-element-uid' => $referenceElementUid[1],
        ];
        return $result;
    }
}