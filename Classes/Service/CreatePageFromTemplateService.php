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
        return $templates;
    }

    /**
     * @param int $templateUid
     * @param int $targetUid
     * @param string $position
     */
    public function createPageFromTemplate(int $templateUid, int $targetUid, string $position = 'inside')
    {
        switch ($position) {
            case 'inside';
                break;
            case 'below';
                $targetUid *= -1;
                break;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $data = [
            'pages' => [
                $templateUid => [
                    'copy' => $targetUid,
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

}