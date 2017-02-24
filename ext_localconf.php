<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function () {
        if (TYPO3_MODE === 'BE') {
            $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['pagetemplates'],
                ['allowed_classes' => false]);
            if ($extensionConfiguration['enableSimpleMode']) {
                $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1487761906] =
                    T3G\Pagetemplates\ContextMenu\CreatePageFromTemplateItemProvider::class;
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
                    options.contextMenu.table.pages.tree.disableItems = new,newWizard
                    options.contextMenu.table.pages.disableItems = new,newWizard
                ');
            }
        }
    }
);


