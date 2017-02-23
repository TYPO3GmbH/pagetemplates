<?php
declare(strict_types = 1);

namespace T3G\Pagetemplates\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use T3G\Pagetemplates\Service\CreatePageFromTemplateService;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This controller is called via the click menu entry "Create page from template.
 * To enable the entry in the menu, you need to enable the simple mode in the
 * extension manger and define the storage folder where you plan to store your
 * templates.
 */
class CreatePageFromTemplateController extends AbstractModule
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->beUser = $this->getBackendUserAuthentication();
        $queryParams = $request->getQueryParams();
        $this->createPageFromTemplateService = GeneralUtility::makeInstance(CreatePageFromTemplateService::class);

        // If a templateUid is transmitted, it is to assume, that this page should be copied to the
        // $queryParams['targetUid'].
        //Afterwards the user will be redirected to the page module of the newly created page

        if ($queryParams['templateUid'] && $queryParams['templateUid'] !== 0) {
            $position = $queryParams['position'] ?: 'firstSubpage';
            $newPageUid = $this->createPageFromTemplateService->createPageFromTemplate((int)$queryParams['templateUid'],
                (int)$queryParams['targetUid'],
                $position);
            $urlParameters = [
                'id' => $newPageUid,
                'table' => 'pages'
            ];
            $url = BackendUtility::getModuleUrl('web_layout', $urlParameters);
            BackendUtility::setUpdateSignal('updatePageTree');
            HttpUtility::redirect($url);
        }

        // Otherwise all templates should be listed.

        $view = $this->getFluidTemplateObject('Main');

        $view->assign('targetUid', (int)$queryParams['targetUid']);
        $view->assign('templates', $this->getTemplates());

        $this->renderModuleHeader((int)$queryParams['targetUid']);
        $this->moduleTemplate->setContent($view->render());
        $response->getBody()->write($this->moduleTemplate->renderContent());

        return $response;


    }

    /**
     * @param int $targetUid
     */
    protected function renderModuleHeader(int $targetUid)
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
        $templates = $this->createPageFromTemplateService->getTemplatesFromDatabase();
        foreach ($templates as $template) {
            if ($this->beUser->doesUserHaveAccess($template, 1)) {
                $allowedTemplatesForUser[] = $template;
            }
        }
        return $allowedTemplatesForUser;
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $action Which templateFile should be used.
     *
     * @return StandaloneView
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
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}