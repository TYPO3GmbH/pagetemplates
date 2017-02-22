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
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

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
    private $pageinfo = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
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
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->main();

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Main processing, creating the list of new record tables to select from
     *
     * @return void
     */
    public function main()
    {
        if ($this->templateUid !== 0) {
            $this->createPageFromTemplate();
        } else {
            $this->renderSelector();
        }
        $this->content .= '<div>' . $this->code . '</div>';
        $this->moduleTemplate->setContent($this->content);

    }

    protected function renderSelector()
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

            //$this->getPositions();
            $this->getTemplates();
        }
    }

    protected function createPageFromTemplate()
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $data = [
            'pages' => [
                $this->templateUid => [
                    'copy' => $this->targetUid,
                ],
            ],
        ];
        $dataHandler->start([], $data);
        $dataHandler->process_cmdmap();

        $newPageUid = $dataHandler->copyMappingArray['pages'][$this->templateUid];

        $urlParameters = [
            'id' => $newPageUid,
            'table' => 'pages'
        ];
        $url = BackendUtility::getModuleUrl('web_layout', $urlParameters);
        @ob_end_clean();
        HttpUtility::redirect($url);
    }

    protected function getPositions()
    {
        $this->code .= '<div>
    <input type="radio" id="firstSubpage" name="position" value="firstSubpage" checked="checked">
    <label for="firstSubpage">Insert as first subpage</label><br> 
    <input type="radio" id="lastSubpage" name="position" value="lastSubpage">
    <label for="lastSubpage">Insert as last subpage</label><br> 
    <input type="radio" id="belowPage" name="position" value="belowPage">
    <label for="belowPage">Insert below this page</label> 
        </div>';
    }

    protected function getTemplates()
    {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['pagetemplates']);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $templates = $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $extensionConfiguration['templateStorageFolder'])
            )
            ->execute()
            ->fetchAll();

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
                        'templateUid' => $template['uid']
                    ]
                )
                ) . '">' . $this->getLanguageService()->sL('LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_as_first_subpage') . '</a></li>';



            $content .= '<li><a href="' . htmlspecialchars(
                GeneralUtility::linkThisScript(
                    [
                        'templateUid' => $template['uid']
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