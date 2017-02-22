<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

use T3G\Pagetemplates\ContextMenu\CreatePageFromTemplateItemProvider;
use T3G\Pagetemplates\Hook\BackendControllerHook;

call_user_func(
    function () {
        if (TYPO3_MODE === 'BE') {
            $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['pagetemplates']);
            if ($extensionConfiguration['enableSimpleMode']) {
                $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1487761906] =
                    CreatePageFromTemplateItemProvider::class;
            }
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = BackendControllerHook::class . '->addJavaScript';
        }
    }
);
