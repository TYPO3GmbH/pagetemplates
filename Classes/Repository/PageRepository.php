<?php
declare(strict_types=1);

namespace T3G\AgencyPack\Pagetemplates\Repository;

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
