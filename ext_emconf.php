<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Page Templates for TYPO3',
    'description' => 'Create new pages from Templates',
    'category' => 'extension',
    'constraints' => [
        'depends' => [
            'typo3' => '8.6.0-8.99.99'
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'T3G\\AgencyPack\\Pagetemplates\\' => 'Classes'
        ],
    ],
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Susanne Moog',
    'author_email' => 'susanne.moog@typo3.com',
    'author_company' => 'TYPO3 GmbH',
    'version' => '0.0.1',
];
