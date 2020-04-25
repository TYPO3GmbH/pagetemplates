<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/pagetemplates.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Pagetemplates\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageRepository
{

    /**
     * Get pages that have a basetemplate set.
     *
     * @return array
     */
    public function getPagesBasedOnTemplates(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return $queryBuilder->select('uid', 'title', 'hidden', 'starttime', 'endtime', 'tx_pagetemplates_basetemplate')
            ->from('pages')
            ->where('tx_pagetemplates_basetemplate <> \'\'')
            ->orderBy('tx_pagetemplates_basetemplate')
            ->execute()
            ->fetchAll();
    }
}
