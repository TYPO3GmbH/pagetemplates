<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/pagetemplates.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Pagetemplates\Controller;

use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Pagetemplates\Service\CreatePageFromTemplateService;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This extends the \TYPO3\CMS\Backend\Controller\NewRecordController to be able to insert custom
 * options before all other options. In this case all pages counting as a template shall be displayed.
 *
 * Class NewRecordController
 *
 */
class NewRecordController extends \TYPO3\CMS\Backend\Controller\NewRecordController
{

    /**
     * Create a regular new element (pages and records)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return void
     */
    protected function renderNewRecordControls(ServerRequestInterface $request): void
    {
        $lang = $this->getLanguageService();
        // Initialize array for accumulating table rows:
        $this->tRows = [];
        // Get TSconfig for current page
        $pageTS = BackendUtility::getPagesTSconfig($this->id);
        // Finish initializing new pages options with TSconfig
        // Each new page option may be hidden by TSconfig
        // Enabled option for the position of a new page
        $this->newPagesSelectPosition = !empty(
        $pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageSelectPosition']
        );
        // Pseudo-boolean (0/1) for backward compatibility
        $displayNewPagesIntoLink = $this->newPagesInto && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageInside']) ? 1 : 0;
        $displayNewPagesAfterLink = $this->newPagesAfter && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageAfter']) ? 1 : 0;
        // Slight spacer from header:
        $this->code .= '';
        // New Page
        $table = 'pages';
        $v = $GLOBALS['TCA'][$table];
        $pageIcon = $this->moduleTemplate->getIconFactory()->getIconForRecord(
            $table,
            [],
            Icon::SIZE_SMALL
        )->render();
        $newPageIcon = $this->moduleTemplate->getIconFactory()->getIcon('actions-page-new', Icon::SIZE_SMALL)->render();
        $rowContent = '';
        // New pages INSIDE this pages
        $newPageLinks = [];
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        if ($displayNewPagesIntoLink && $this->isTableAllowedOnPage(
            'pages',
            $this->pageinfo
        ) && $backendUserAuthentication->check(
            'tables_modify',
            'pages'
        ) && $backendUserAuthentication->workspaceCreateNewRecord(
            ($this->pageinfo['_ORIG_uid'] ?: $this->id),
            'pages'
        )
        ) {
            // Create link to new page inside:
            $newPageLinks[] = $this->renderLink(
                $this->moduleTemplate->getIconFactory()->getIconForRecord(
                    $table,
                    [],
                    Icon::SIZE_SMALL
                )->render() .
                htmlspecialchars($lang->sL($v['ctrl']['title']), ENT_QUOTES | ENT_HTML5) .
                ' (' .
                htmlspecialchars(
                    $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:db_new.php.inside'),
                    ENT_QUOTES | ENT_HTML5
                ) .
                ')',
                $table,
                $this->id
            );
        }
        // New pages AFTER this pages
        if ($displayNewPagesAfterLink && $this->isTableAllowedOnPage(
            'pages',
            $this->pidInfo
        ) && $backendUserAuthentication->check(
            'tables_modify',
            'pages'
        ) && $backendUserAuthentication->workspaceCreateNewRecord(
            $this->pidInfo['uid'],
            'pages'
        )
        ) {
            $newPageLinks[] = $this->renderLink(
                $pageIcon .
                htmlspecialchars($lang->sL($v['ctrl']['title']), ENT_QUOTES | ENT_HTML5) . ' (' .
                htmlspecialchars(
                    $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:db_new.php.after'),
                    ENT_QUOTES | ENT_HTML5
                ) . ')',
                'pages',
                -$this->id
            );
        }
        // New pages at selection position
        if ($this->newPagesSelectPosition && $this->isRecordCreationAllowedForTable('pages')) {
            // Link to page-wizard:
            $newPageLinks[] = '<a href="' .
                              htmlspecialchars(
                                  GeneralUtility::linkThisScript(['pagesOnly' => 1]),
                                  ENT_QUOTES | ENT_HTML5
                              ) . '">' . $pageIcon .
                              htmlspecialchars($lang->getLL('pageSelectPosition'), ENT_QUOTES | ENT_HTML5) . '</a>';
        }
        // Assemble all new page links
        foreach ($newPageLinks as $iValue) {
            $rowContent .= '<li>' . $iValue . '</li>';
        }
        if ($this->isRecordCreationAllowedForTable('pages')) {
            $rowContent = '<li>' . $newPageIcon . '<strong>' .
                          $lang->getLL('createNewPage') . '</strong><ul>' . $rowContent . '</ul></li>';
        } else {
            $rowContent = '<li><ul>' . $rowContent . '</ul></li>';
        }

        // Create page from template start

        // Compile table row
        $startRows = [
            $this->getCreatePageFromTemplateBlock(),
            $rowContent,
        ];

        // Create page from template end

        $iconFile = [];
        // New tables (but not pages) INSIDE this pages
        $isAdmin = $backendUserAuthentication->isAdmin();
        $newContentIcon = $this->moduleTemplate->getIconFactory()->getIcon(
            'actions-document-new',
            Icon::SIZE_SMALL
        )->render();
        if ($this->newContentInto && is_array($GLOBALS['TCA'])) {
            $groupName = '';
            foreach ($GLOBALS['TCA'] as $table => $v) {
                $rootLevelConfiguration = isset($v['ctrl']['rootLevel']) ? (int)$v['ctrl']['rootLevel'] : 0;
                if ($table !== 'pages'
                    && $this->isRecordCreationAllowedForTable($table)
                    && $this->isTableAllowedOnPage($table, $this->pageinfo)
                    && $backendUserAuthentication->check('tables_modify', $table)
                    && ($rootLevelConfiguration === -1 || ($this->id xor $rootLevelConfiguration))
                    && $backendUserAuthentication->workspaceCreateNewRecord(
                        ($this->pageinfo['_ORIG_uid'] ?: $this->id),
                        $table
                    )
                ) {
                    $newRecordIcon = $this->moduleTemplate->getIconFactory()->getIconForRecord(
                        $table,
                        [],
                        Icon::SIZE_SMALL
                    )->render();
                    $rowContent = '';
                    $thisTitle = '';
                    // Create new link for record:
                    $newLink = $this->renderLink(
                        $newRecordIcon . htmlspecialchars($lang->sL($v['ctrl']['title']), ENT_QUOTES | ENT_HTML5),
                        $table,
                        $this->id
                    );
                    // If the table is 'tt_content', create link to wizard
                    if ($table === 'tt_content') {
                        $groupName = $lang->getLL('createNewContent');
                        $rowContent = $newContentIcon . '<strong>' . $lang->getLL('createNewContent') . '</strong><ul>';
                        // If mod.newContentElementWizard.override is set, use that extension's wizard instead:
                        $tsConfig = BackendUtility::getPagesTSconfig($this->id)['mod'];
                        $moduleName = $tsConfig['properties']['newContentElementWizard.']['override']
                                      ??
                                      'new_content_element';
                        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                        $url = $uriBuilder->buildUriFromRoute(
                            $moduleName,
                            ['id' => $this->id, 'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')]
                        );
                        $rowContent .= '<li>' . $newLink . ' ' . BackendUtility::wrapInHelp(
                            $table,
                            ''
                        ) . '</li><li><a href="' .
                                       htmlspecialchars($url, ENT_QUOTES | ENT_HTML5) . '">' . $newContentIcon .
                                       htmlspecialchars($lang->getLL('clickForWizard'), ENT_QUOTES | ENT_HTML5) . '</a></li></ul>';
                    } else {
                        // Get the title
                        if ($v['ctrl']['readOnly'] || $v['ctrl']['hideTable'] || $v['ctrl']['is_static']) {
                            continue;
                        }
                        if (!$isAdmin && $v['ctrl']['adminOnly']) {
                            continue;
                        }
                        $nameParts = explode('_', $table);
                        $thisTitle = '';
                        $_EXTKEY = '';
                        if ($nameParts[0] === 'tx' || $nameParts[0] === 'tt') {
                            // Try to extract extension name
                            if (strpos($v['ctrl']['title'], 'LLL:EXT:') === 0) {
                                $_EXTKEY = substr($v['ctrl']['title'], 8);
                                $_EXTKEY = substr($_EXTKEY, 0, strpos($_EXTKEY, '/'));
                                if ($_EXTKEY !== '') {
                                    // First try to get localisation of extension title
                                    $temp = explode(':', substr($v['ctrl']['title'], 9 + strlen($_EXTKEY)));
                                    $langFile = $temp[0];
                                    $thisTitle = $lang->sL('LLL:EXT:' . $_EXTKEY . '/' . $langFile . ':extension.title');
                                    // If no localisation available, read title from ext_emconf.php
                                    $extPath = ExtensionManagementUtility::extPath($_EXTKEY);
                                    $extEmConfFile = $extPath . 'ext_emconf.php';
                                    if (!$thisTitle && is_file($extEmConfFile)) {
                                        $EM_CONF = [];
                                        include $extEmConfFile;
                                        $thisTitle = $EM_CONF[$_EXTKEY]['title'];
                                    }
                                    $iconFile[$_EXTKEY] = '<img src="' . PathUtility::getAbsoluteWebPath(
                                        ExtensionManagementUtility::getExtensionIcon(
                                            $extPath,
                                            true
                                        )
                                    ) . '" ' . 'width="16" height="16" ' . 'alt="' . $thisTitle . '" />';
                                }
                            }
                            if (empty($thisTitle)) {
                                $_EXTKEY = $nameParts[1];
                                $thisTitle = $nameParts[1];
                                $iconFile[$_EXTKEY] = '';
                            }
                        } else {
                            if ($table === 'pages_language_overlay' && !$this->checkIfLanguagesExist()) {
                                continue;
                            }
                            $_EXTKEY = 'system';
                            $thisTitle = $lang->getLL('system_records');
                            $iconFile['system'] = $this->moduleTemplate->getIconFactory()->getIcon(
                                'apps-pagetree-root',
                                Icon::SIZE_SMALL
                            )->render();
                        }

                        if ($groupName === '' || $groupName !== $_EXTKEY) {
                            $groupName = empty($v['ctrl']['groupName']) ? $_EXTKEY : $v['ctrl']['groupName'];
                        }
                        $rowContent .= $newLink;
                    }
                    // Compile table row:
                    if ($table === 'tt_content') {
                        $startRows[] = '<li>' . $rowContent . '</li>';
                    } else {
                        $this->tRows[$groupName]['title'] = $thisTitle;
                        $this->tRows[$groupName]['html'][] = $rowContent;
                        $this->tRows[$groupName]['table'][] = $table;
                    }
                }
            }
        }
        // User sort
        if (isset($pageTS['mod.']['wizards.']['newRecord.']['order'])) {
            $this->newRecordSortList = GeneralUtility::trimExplode(
                ',',
                $pageTS['mod.']['wizards.']['newRecord.']['order'],
                true
            );
        }
        uksort($this->tRows, [$this, 'sortNewRecordsByConfig']);
        // Compile table row:
        $finalRows = ['<ul class="list-tree">'];
        $finalRows[] = implode('', $startRows);

        foreach ($this->tRows as $key => $value) {
            $row = '<li>' . $iconFile[$key] . ' <strong>' . $value['title'] . '</strong><ul>';
            foreach ($value['html'] as $recordKey => $record) {
                $row .= '<li>' . $record . ' ' . BackendUtility::wrapInHelp($value['table'][$recordKey], '') . '</li>';
            }
            $row .= '</ul></li>';
            $finalRows[] = $row;
        }

        $finalRows[] = '</ul>';
        // Make table:
        $this->code .= implode('', $finalRows);
    }

    protected function getCreatePageFromTemplateBlock(): string
    {
        $rows = [];
        $pageIcon = $this->moduleTemplate
            ->getIconFactory()
            ->getIconForRecord('pages', [], Icon::SIZE_SMALL)
            ->render();

        $templates = $this->getTemplates();
        if (!empty($templates)) {
            foreach ($templates as $template) {
                $linkRows = [];
                foreach (['firstSubpage', 'lastSubpage', 'below'] as $position) {
                    $moduleUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                        'create-page-from-template',
                        [
                            'targetUid' => $this->id,
                            'templateUid' => $template['uid'],
                            'position' => $position,

                        ]
                    );
                    $linkRows[] = '<li><a href=' .
                                  $moduleUrl .
                                  '>' .
                                  $this->getLanguageService()->sL('LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_' . $position) .
                                  '</a></li>';
                }
                $rows[] = '<li>' . $pageIcon . $template['title'] . '<ul>' . implode('', $linkRows) . '</ul></li>';
            }
        }
        $newPageIcon = $this->moduleTemplate->getIconFactory()->getIcon('actions-page-new', Icon::SIZE_SMALL)->render();
        return '<li>' . $newPageIcon . '<strong>' .
               $this->getLanguageService()->sL('LLL:EXT:pagetemplates/Resources/Private/Language/locallang.xlf:label.create_page_from_template') . '</strong><ul>' . implode(
                   '',
                   $rows
               ) . '</ul></li>';
    }

    /**
     * @return array
     */
    protected function getTemplates(): array
    {
        $allowedTemplatesForUser = [];
        $createPageFromTemplateService = GeneralUtility::makeInstance(CreatePageFromTemplateService::class);
        foreach ($createPageFromTemplateService->getTemplatesFromDatabase() as $template) {
            if ($this->getBackendUserAuthentication()->doesUserHaveAccess($template, 1)) {
                $allowedTemplatesForUser[] = $template;
            }
        }
        return $allowedTemplatesForUser;
    }
}
