<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/pagetemplates.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Pagetemplates\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Pagetemplates\Service\CreatePageFromTemplateService;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This controller is called via the click menu entry "Create page from template."
 * To enable the entry in the menu, you need to enable the simple mode in the
 * extension manger and define the storage folder where you plan to store your
 * templates.
 */
class CreatePageFromTemplateController
{

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var CreatePageFromTemplateService
     */
    protected $createPageFromTemplateService;

    /**
     * @var BackendUserAuthentication
     */
    protected $beUser;
    /**
     * @var object|\TYPO3\CMS\Backend\Template\ModuleTemplate
     */
    protected $moduleTemplate;

    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    public function mainAction(ServerRequestInterface $request): ?ResponseInterface
    {
        $this->beUser = $this->getBackendUserAuthentication();
        $queryParams = $request->getQueryParams();
        $this->createPageFromTemplateService = GeneralUtility::makeInstance(CreatePageFromTemplateService::class);
        if (isset($queryParams['templateUid']) && $queryParams['templateUid'] !== 0) {
            $this->createPageFromTemplateAndRedirectToPageModule($queryParams);
        } else {
            return $this->showListOfTemplates($queryParams);
        }
    }

    /**
     * @param $queryParams
     */
    protected function createPageFromTemplateAndRedirectToPageModule($queryParams): void
    {
        $position = $queryParams['position'] ?: 'firstSubpage';
        $newPageUid = $this->createPageFromTemplateService->createPageFromTemplate(
            (int)$queryParams['templateUid'],
            (int)$queryParams['targetUid'],
            $position
        );
        $urlParameters = [
            'id' => $newPageUid,
            'table' => 'pages'
        ];
        $url = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('web_layout', $urlParameters);
        BackendUtility::setUpdateSignal('updatePageTree');
        HttpUtility::redirect($url);
    }

    /**
     * @param $queryParams
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    protected function showListOfTemplates($queryParams): ResponseInterface
    {
        $view = $this->getFluidTemplateObject('Main');
        $view->assign('targetUid', (int)$queryParams['targetUid']);
        $view->assign('templates', $this->getTemplates());
        $this->renderModuleHeader((int)$queryParams['targetUid']);
        $this->moduleTemplate->setContent($view->render());
        return GeneralUtility::makeInstance(HtmlResponse::class, $this->moduleTemplate->renderContent());
    }

    /**
     * @param int $targetUid
     */
    protected function renderModuleHeader(int $targetUid): void
    {
        if ($targetUid > 0) {
            $pageInfo = BackendUtility::readPageAccess($targetUid, $this->beUser->getPagePermsClause(1));
        }
        // If there was a page - or if the user is admin (admins has access to the root) we proceed:
        if (!empty($pageInfo['uid']) || $this->beUser->isAdmin()) {
            if (empty($pageInfo)) {
                // Explicitly pass an empty array to the docHeader
                $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);
            } else {
                $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageInfo);
            }
            // Set header-HTML and return_url
            if (is_array($pageInfo) && $pageInfo['uid']) {
                $title = strip_tags($pageInfo[$GLOBALS['TCA']['pages']['ctrl']['label']]);
            } else {
                $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
            }
            $this->moduleTemplate->setTitle($title);
        }
    }

    /**
     * @return array
     */
    protected function getTemplates(): array
    {
        $allowedTemplatesForUser = [];
        foreach ($this->createPageFromTemplateService->getTemplatesFromDatabase() as $template) {
            if ($this->beUser->doesUserHaveAccess($template, Permission::PAGE_SHOW)) {
                $allowedTemplatesForUser[] = $template;
            }
        }
        return $allowedTemplatesForUser;
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $action Which templateFile should be used.
     * @return StandaloneView
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    protected function getFluidTemplateObject(string $action): StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:pagetemplates/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:pagetemplates/Resources/Private/Partials/CreatePageFromTemplate')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:pagetemplates/Resources/Private/Templates/CreatePageFromTemplate')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:pagetemplates/Resources/Private/Templates/CreatePageFromTemplate/' . $action . '.html'));

        $view->getRequest()->setControllerExtensionName('Pagetemplates');
        return $view;
    }

    /**
     * Returns the global BackendUserAuthentication object.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
