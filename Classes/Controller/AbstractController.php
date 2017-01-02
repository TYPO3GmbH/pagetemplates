<?php
declare(strict_types = 1);


namespace T3G\Pagetemplates\Controller;


use T3G\Pagetemplates\View\BackendTemplateView;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\MenuRegistry;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class AbstractController extends ActionController
{
    const MODULE_NAME = 'web_PagetemplatesTxPagetemplates';
    /**
     * @var ButtonBar
     */
    protected $buttonBar;
    /**
     * @var MenuRegistry
     */
    protected $menuRegistry;
    /**
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;
    /**
     * @var PageRenderer
     */
    protected $pageRenderer;
    /**
     * @var FlashMessageService
     */
    protected $flashMessageService;
    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;


    /**
     * Initialize View.
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        if ($view instanceof BackendTemplateView) {
            $this->moduleTemplate = $view->getModuleTemplate();
            $this->pageRenderer = $this->moduleTemplate->getPageRenderer();
            $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
            $this->menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
            $menuConfiguration = [
                [
                    'controller' => 'Wizard',
                    'action' => 'index',
                    'label' => 'Wizard',
                ],
                [
                    'controller' => 'Management',
                    'action' => 'basedOn',
                    'label' => 'Based on',
                ],
            ];
            $this->createMenu('pagetemplates_menu', $menuConfiguration);
        }
    }

    /**
     * Initialize Action.
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        $this->flashMessageService = $this->objectManager->get(
            FlashMessageService::class
        );
        $this->uriBuilder = $this->objectManager->get(UriBuilder::class);
        $this->uriBuilder->setRequest($this->request);
    }

    /**
     * Create backend toolbar menu.
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
