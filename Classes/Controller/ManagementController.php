<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/pagetemplates.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Pagetemplates\Controller;

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
    public function injectPageRepository(PageRepository $pageRepository): void
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * Action to display pages based on templates
     */
    public function basedOnAction(): void
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
