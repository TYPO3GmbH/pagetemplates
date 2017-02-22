<?php
declare(strict_types = 1);

namespace T3G\Pagetemplates\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

class CreatePageFromTemplateService
{

    /**
     * @return array
     */
    public function getTemplatesFromDatabase(): array
    {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['pagetemplates'], ['allowed_classes' => false]);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        $templates = $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $extensionConfiguration['templateStorageFolder'])
            )
            ->execute()
            ->fetchAll();
        return $templates;
    }

    /**
     * @param int $templateUid
     * @param int $targetUid
     * @param string $position
     */
    public function createPageFromTemplate(int $templateUid, int $targetUid, string $position)
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

        $newPageUid = $dataHandler->copyMappingArray['pages'][$templateUid];

        $urlParameters = [
            'id' => $newPageUid,
            'table' => 'pages'
        ];
        $url = BackendUtility::getModuleUrl('web_layout', $urlParameters);
        @ob_end_clean();
        HttpUtility::redirect($url);
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
                $queryBuilder->expr()->eq('pid', $targetUid)
            )
            ->orderBy('sorting', 'DESC')
            ->execute()
            ->fetch();
        return (int)$templates['uid'];
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
                $targetUid = 0 - $this->getUidOfLastSubpage($targetUid);
                break;
            case 'firstSubpage';
            default:
                break;
        }
        return $targetUid;
    }
}