<?php
declare(strict_types = 1);


namespace T3G\Pagetemplates\Controller;


use T3G\Pagetemplates\Repository\PageRepository;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

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
