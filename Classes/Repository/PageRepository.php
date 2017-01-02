<?php
declare(strict_types = 1);


namespace T3G\Pagetemplates\Repository;


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageRepository
{

    public function getPagesBasedOnTemplates()
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return $queryBuilder->select('uid', 'title', 'hidden', 'starttime', 'endtime', 'tx_pagetemplates_basetemplate')
            ->from('pages')
            ->where('tx_pagetemplates_basetemplate <> \'\'')
            ->orderBy('tx_pagetemplates_basetemplate')
            ->execute()
            ->fetchAll();
    }
}
