<?php

return [
    // Register createPageFromTemplate controller
    'create-page-from-template' => [
        'path' => '/context-menu/pagetemplates/create-page-from-template',
        'target' => \T3G\Pagetemplates\Controller\CreatePageFromTemplateController::class . '::mainAction'
    ]
];
