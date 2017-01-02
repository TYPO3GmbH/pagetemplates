<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function () {
        if (TYPO3_MODE === 'BE') {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'T3G.Pagetemplates',
                'web',
                'tx_Pagetemplates',
                '',
                [
                    'Wizard' => 'index,create,saveNewPage',
                    'Management' => 'basedOn'
                ],
                [
                    'access' => 'user,group',
                    'icon' => 'EXT:pagetemplates/Resources/Public/Icons/module.svg',
                    'labels' => 'LLL:EXT:pagetemplates/Resources/Private/Language/locallang_mod.xlf',
                ]
            );
        }
    }
);
