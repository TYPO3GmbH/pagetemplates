<?php
declare(strict_types = 1);


namespace T3G\Pagetemplates\Controller;


use T3G\Pagetemplates\Provider\TemplateProvider;
use T3G\Pagetemplates\Service\FormEngineService;
use T3G\T3Extended\Controller\BackendActionController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class BackendController extends BackendActionController
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

    public function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $this->pageRenderer->addCssFile(
            'EXT:pagetemplates/Resources/Public/Css/backend.css'
        );
        $menuConfiguration = [
            [
                'controller' => 'Backend',
                'action' => 'index',
                'label' => 'Index',
            ],
        ];
        $this->createMenu('pagetemplates_menu', $menuConfiguration);
    }

    public function initializeAction()
    {
        parent::initializeAction();
        $pagesTSconfig = BackendUtility::getPagesTSconfig((int)$_GET['id']);
        $this->configPath = GeneralUtility::getFileAbsFileName($pagesTSconfig['mod.'][self::MODULE_NAME . '.']['storagePath']);
        $this->setBackendModuleTemplates();
        $this->templateProvider = $this->objectManager->get(TemplateProvider::class, $this->configPath);
    }

    public function indexAction()
    {
        $templates = $this->templateProvider->getTemplates();
        $this->view->assign('templates', $templates);
    }

    /**
     * @param string $templateIdentifier
     */
    public function createAction(string $templateIdentifier)
    {
        $configuration = $this->templateProvider->getTemplateConfiguration($templateIdentifier);

        $formEngineService = $this->objectManager->get(FormEngineService::class);
        $html = $formEngineService->createEditForm($configuration);
        if ($html !== '') {
            $this->view->assign('html', $html);
        } else {
            $this->redirect('saveNewPage');
        }
    }

    public function saveNewPageAction()
    {
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $data = $_POST['data'];
        if (array_key_exists('tt_content', $data) && is_array($data['tt_content'])) {
            arsort($data['tt_content']);
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

}
