<?php

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

return [
    // Register createPageFromTemplate controller
    'create-page-from-template' => [
        'path' => '/context-menu/pagetemplates/create-page-from-template',
        'target' => \T3G\AgencyPack\Pagetemplates\Controller\CreatePageFromTemplateController::class . '::mainAction',
    ],
    // Override a core route to substitute it with an own controller.
    'db_new' => [
        'path' => '/record/new',
        'target' => \T3G\AgencyPack\Pagetemplates\Controller\NewRecordController::class . '::mainAction',
    ],
];
