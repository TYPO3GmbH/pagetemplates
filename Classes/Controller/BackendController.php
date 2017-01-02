<?php
declare(strict_types = 1);


namespace T3G\Pagetemplates\Controller;

use T3G\Pagetemplates\Provider\TemplateProvider;
use T3G\Pagetemplates\Service\FormEngineService;
use T3G\Pagetemplates\View\BackendTemplateView;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\MenuRegistry;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class BackendController extends ActionController
{
    const MODULE_NAME = 'web_PagetemplatesTxPagetemplates';

    /**
     * @var TemplateProvider
     */
    protected $templateProvider;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * @var ButtonBar
     */
    protected $buttonBar;

    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var MenuRegistry
     */
    protected $menuRegistry;

    /**
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Initialize view and add Css
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $this->moduleTemplate = $view->getModuleTemplate();
        $this->pageRenderer = $this->moduleTemplate->getPageRenderer();
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $this->menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
        $menuConfiguration = [
            [
                'controller' => 'Backend',
                'action' => 'index',
                'label' => 'Index',
            ],
        ];
        $this->createMenu('pagetemplates_menu', $menuConfiguration);
    }

    /**
     * Initialize action
     * fetches storage path from TSConfig
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        $this->uriBuilder = $this->objectManager->get(UriBuilder::class);
        $this->uriBuilder->setRequest($this->request);
        $pagesTSconfig = BackendUtility::getPagesTSconfig((int)$_GET['id']);
        $this->configPath = GeneralUtility::getFileAbsFileName($pagesTSconfig['mod.'][self::MODULE_NAME . '.']['storagePath']);
        $this->setBackendModuleTemplates();
        $this->templateProvider = $this->objectManager->get(TemplateProvider::class, $this->configPath);
    }

    /**
     * Display available templates
     */
    public function indexAction()
    {
        $templates = $this->templateProvider->getTemplates();
        $this->view->assign('templates', $templates);
    }

    /**
     * Display the edit form for the chosen template
     *
     * @param string $templateIdentifier
     */
    public function createAction(string $templateIdentifier)
    {
        $configuration = $this->templateProvider->getTemplateConfiguration($templateIdentifier);

        $formEngineService = $this->objectManager->get(FormEngineService::class);
        $forms = $formEngineService->createEditForm($configuration);
        $this->view->assign('forms', $forms);

    }

    /**
     * save template as new page
     * and send the user to the page module
     */
    public function saveNewPageAction()
    {
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $data = $_POST['data'];
        // sort data to get the same order as when entering it
        foreach ($data as $table => &$elements) {
            arsort($elements);
        }
        $newPageIdentifier = key($data['pages']);
        $tce->start($data, []);
        $tce->process_datamap();
        BackendUtility::setUpdateSignal('updatePageTree');
        $realPid = $tce->substNEWwithIDs[$newPageIdentifier];

        $pageModuleUrl = BackendUtility::getModuleUrl('web_layout', ['id' => $realPid]);
        $this->redirectToUri($pageModuleUrl);
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
                'templateRootPaths' => ['EXT:pagetemplates/Resources/Private/Backend/Templates'],
                'partialRootPaths' => ['EXT:pagetemplates/Resources/Private/Backend/Partials'],
                'layoutRootPaths' => ['EXT:pagetemplates/Resources/Private/Backend/Layouts'],
            ],
        ];
        $this->configurationManager->setConfiguration(array_merge($frameworkConfiguration, $viewConfiguration));
    }

    /**
     * create backend toolbar menu
     *
     * @param string $identifier
     * @param array $menuConfiguration (needs to have the following keys: "controller", "action", "label")
     * @api
     */
    protected function createMenu(string $identifier, array $menuConfiguration)
    {
        $menu = $this->menuRegistry->makeMenu();
        $menu->setIdentifier($identifier);

        foreach ($menuConfiguration as $menuItemConfiguration) {
            $menuItem = $menu->makeMenuItem();
            $isActive = $this->request->getControllerActionName() === $menuItemConfiguration['action'];
            $uri = $this->uriBuilder->reset()->uriFor($menuItemConfiguration['action'], [], $menuItemConfiguration['controller']);
            $menuItem
                ->setTitle($menuItemConfiguration['label'])
                ->setHref($uri)
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
        }

        $this->menuRegistry->addMenu($menu);
    }
}
