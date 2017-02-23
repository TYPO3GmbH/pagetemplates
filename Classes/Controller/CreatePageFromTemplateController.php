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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * This controller is called via the click menu entry "Create page from template.
 * To enable the entry in the menu, you need to enable the simple mode in the 
 * extension manger and define the storage folder where you plan to store your
 * templates.  
 */
class CreatePageFromTemplateController extends AbstractModule
{

    /**
     * @var CreatePageFromTemplateService
     */
    protected $createPageFromTemplateService;


    /**
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->createPageFromTemplateService = GeneralUtility::makeInstance(CreatePageFromTemplateService::class);
        $targetUid = (int)GeneralUtility::_GP('targetUid');
        $templateUid = (int)GeneralUtility::_GP('templateUid');

        $templateSelectorHtml = '';

        if ($templateUid !== 0) {
            // If a templateUid is transmitted, it is to assume, that this page should be copied to the $targetUid.
            $position = GeneralUtility::_GP('position') ?: 'firstSubpage';
            $newPageUid = $this->createPageFromTemplateService->createPageFromTemplate($templateUid, $targetUid, $position);
            $urlParameters = [
                'id' => $newPageUid,
                'table' => 'pages'
            ];
            $url = BackendUtility::getModuleUrl('web_layout', $urlParameters);
            BackendUtility::setUpdateSignal('updatePageTree');
            HttpUtility::redirect($url);
        } else {
            // Otherwise all templates should be listed.
            $this->renderModuleHeader($targetUid);
            $templateSelectorHtml = $this->renderTemplateSelector();
        }
        $content = '<h1>'
            . $this->getLanguageService()->sL('LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_page_from_template')
            . '</h1>';
        $content .= $templateSelectorHtml;
        $this->moduleTemplate->setContent($content);

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @param int $targetUid
     */
    protected function renderModuleHeader(int $targetUid)
    {
        $beUser = $this->getBackendUserAuthentication();
        if ($targetUid > 0) {
            $pageInfo = BackendUtility::readPageAccess($targetUid, $beUser->getPagePermsClause(1));
        }
        // If there was a page - or if the user is admin (admins has access to the root) we proceed:
        if (!empty($pageInfo['uid']) || $beUser->isAdmin()) {
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
     *
     */
    protected function renderTemplateSelector()
    {
        $templates = $this->createPageFromTemplateService->getTemplatesFromDatabase();

        $pageIcon = $this->moduleTemplate
            ->getIconFactory()
            ->getIconForRecord('pages', [], Icon::SIZE_SMALL)
            ->render();

        $content = '<ul>';

        foreach ($templates as $template) {
            $content .= '<li>' . $pageIcon . htmlspecialchars($template['title']) . '<ul>';
            $content .= '<li><a href="' . htmlspecialchars(
                    GeneralUtility::linkThisScript(
                        [
                            'templateUid' => $template['uid'],
                            'position' => 'firstSubpage'
                        ]
                    )
                ) . '">' . $this->getLanguageService()->sL('LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_as_first_subpage') . '</a></li>';

            $content .= '<li><a href="' . htmlspecialchars(
                    GeneralUtility::linkThisScript(
                        [
                            'templateUid' => $template['uid'],
                            'position' => 'lastSubpage'
                        ]
                    )
                ) . '">' . $this->getLanguageService()->sL('LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_as_last_subpage') . '</a></li>';

            $content .= '<li><a href="' . htmlspecialchars(
                    GeneralUtility::linkThisScript(
                        [
                            'templateUid' => $template['uid'],
                            'position' => 'below'
                        ]
                    )
                ) . '">' . $this->getLanguageService()->sL('LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_below_this_page') . '</a></li>';
            $content .= '</ul></li>';

        }
        $content .= '</ul>';
        return '<div>' . $content . '</div>';
    }

    /**
     * Return language service instance
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the global BackendUserAuthentication object.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }


}