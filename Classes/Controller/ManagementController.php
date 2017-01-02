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

    public function injectPageRepository(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    protected function initializeAction()
    {
        parent::initializeAction();
        $this->setBackendModuleTemplates();
    }

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


    /**
     * Set Backend Module Templates
     *
     * @return void
     */
    private function setBackendModuleTemplates()
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $viewConfiguration = [
            'view' => [
                'templateRootPaths' => ['EXT:pagetemplates/Resources/Private/Backend/Management/Templates'],
                'partialRootPaths' => ['EXT:pagetemplates/Resources/Private/Backend/Management/Partials'],
                'layoutRootPaths' => ['EXT:pagetemplates/Resources/Private/Backend/Management/Layouts'],
            ],
        ];
        $this->configurationManager->setConfiguration(array_merge($frameworkConfiguration, $viewConfiguration));
    }
}
