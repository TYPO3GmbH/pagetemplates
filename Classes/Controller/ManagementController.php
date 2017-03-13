<?php
declare(strict_types=1);

namespace T3G\AgencyPack\Pagetemplates\Controller;

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

use T3G\AgencyPack\Pagetemplates\Repository\PageRepository;

class ManagementController extends AbstractController
{
    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * Inject page repository.
     *
     * @param PageRepository $pageRepository
     */
    public function injectPageRepository(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * Action to display pages based on templates
     */
    public function basedOnAction()
    {
        $pagesBasedOnTemplates = $this->pageRepository->getPagesBasedOnTemplates();
        $this->view
            ->assign(
                'pages',
                $pagesBasedOnTemplates
            )
            ->assign(
                'returnUrl',
                urlencode($this->uriBuilder->reset()->uriFor('basedOn', [], 'Management'))
            );
    }
}
