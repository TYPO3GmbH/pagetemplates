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

class CreatePageFromTemplateController extends AbstractModule
{
    /**
     * Accumulated HTML output
     *
     * @var string
     */
    protected $content = '';

    /**
     * @var int
     */
    protected $targetUid = 0;

    /**
     * @var int
     */
    protected $templateUid = 0;

    /**
     * @var string
     */
    protected $returnUrl = '';

    /**
     * @var string
     */
    protected $code = '';

    /**
     * @var array
     */
    protected $pageinfo = [];

    /**
     * @var CreatePageFromTemplateService
     */
    protected $service;

    /**
     * @var string
     */
    protected $position = 'inside';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = GeneralUtility::makeInstance(CreatePageFromTemplateService::class);
        $GLOBALS['SOBE'] = $this;
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_misc.xlf');
        $this->init();
    }

    protected function init()
    {
        $beUser = $this->getBackendUserAuthentication();

        // Setting GPvars:
        // The page id to operate from
        $this->targetUid = (int)GeneralUtility::_GP('targetUid');
        $this->templateUid = (int)GeneralUtility::_GP('templateUid');
        $this->position = GeneralUtility::_GP('position');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));

        // Creating content
        $this->content = '';
        $this->content .= '<h1>'
            . $this->getLanguageService()->sL('LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_page_from_template')
            . '</h1>';
        // If a positive id is supplied, ask for the page record with permission information contained:
        if ($this->targetUid > 0) {
            $this->pageinfo = BackendUtility::readPageAccess($this->targetUid, $beUser->getPagePermsClause(1));
        }
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->templateUid !== 0) {
            $this->service->createPageFromTemplate($this->templateUid, $this->targetUid, $this->position);
        } else {
            $this->renderModuleHeader();
            $this->renderTemplateSelector();
        }
        $this->content .= '<div>' . $this->code . '</div>';
        $this->moduleTemplate->setContent($this->content);

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     *
     */
    protected function renderModuleHeader()
    {
        // If there was a page - or if the user is admin (admins has access to the root) we proceed:
        if (!empty($this->pageinfo['uid']) || $this->getBackendUserAuthentication()->isAdmin()) {
            if (empty($this->pageinfo)) {
                // Explicitly pass an empty array to the docHeader
                $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);
            } else {
                $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
            }
            // Set header-HTML and return_url
            if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
                $title = strip_tags($this->pageinfo[$GLOBALS['TCA']['pages']['ctrl']['label']]);
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
        $templates = $this->service->getTemplatesFromDatabase();

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
                            'position' => 'inside'
                        ]
                    )
                ) . '">' . $this->getLanguageService()->sL('LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_as_first_subpage') . '</a></li>';

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
        $this->code .= '<div>' . $content . '</div>';
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