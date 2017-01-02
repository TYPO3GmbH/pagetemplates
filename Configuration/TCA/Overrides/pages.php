<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TCA']['pages']['columns']['tx_pagetemplates_basetemplate'] = [
    'config' => [
        'type' => 'passthrough',
    ],
];

