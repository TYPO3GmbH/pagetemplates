<?php
declare(strict_types = 1);

namespace T3G\Pagetemplates\Service;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CreatePageFromTemplateService
{

    /**
     * @return array
     */
    public function getTemplatesFromDatabase(): array
    {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['pagetemplates'],
            ['allowed_classes' => false]);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        $templates = $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($extensionConfiguration['templateStorageFolder'])
                )
            )
            ->orderBy('sorting')
            ->execute()
            ->fetchAll();
        return $templates;
    }

    /**
     * @param int $templateUid
     * @param int $targetUid
     * @param string $position
     * @return int
     */
    public function createPageFromTemplate(int $templateUid, int $targetUid, string $position): int
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $data = [
            'pages' => [
                $templateUid => [
                    'copy' => $this->getManipulatedTargetUidForDataHandler($targetUid, $position),
                ],
            ],
        ];
        $dataHandler->start([], $data);
        $dataHandler->process_cmdmap();

        return (int)$dataHandler->copyMappingArray['pages'][$templateUid];
    }

    /**
     * @param int $targetUid
     * @param string $position
     * @return int
     */
    protected function getManipulatedTargetUidForDataHandler(int $targetUid, string $position): int
    {
        switch ($position) {
            case 'below';
                $targetUid *= -1;
                break;
            case 'lastSubpage';
                $uidOfLastSubpage = $this->getUidOfLastSubpage($targetUid);
                // Only change the target uid, if the current page has at least one subpage.
                if ($uidOfLastSubpage !== 0) {
                    $targetUid = 0 - $uidOfLastSubpage;
                }
                break;
            case 'firstSubpage';
                // Nothing to change here, because this is default.
                break;
            default:
                throw new \InvalidArgumentException('The given position didn\'t match the allowed.', 1487851947);
        }
        return $targetUid;
    }

    /**
     * @param int $targetUid
     * @return int
     */
    protected function getUidOfLastSubpage(int $targetUid): int
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        $templates = $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($targetUid)
                )
            )
            ->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        if (!empty($templates)) {
            return (int)$templates['uid'];
        }
        return 0;
    }
}