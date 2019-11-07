<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/pagetemplates.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Pagetemplates\Controller;

use T3G\AgencyPack\Pagetemplates\Provider\TemplateProvider;
use T3G\AgencyPack\Pagetemplates\Service\FormEngineService;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class WizardController extends AbstractController
{

    /**
     * @var TemplateProvider
     */
    protected $templateProvider;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * Add flash message if the config directory cannot be found.
     */
    protected function addNoConfigFoundError(): void
    {
        $headline = LocalizationUtility::translate('config_dir_not_found.headline', 'pagetemplates');
        $message = sprintf(
            LocalizationUtility::translate('config_dir_not_found.message', 'pagetemplates'),
            htmlspecialchars(str_replace(PATH_site, '', $this->configPath), ENT_QUOTES | ENT_HTML5)
        );
        $flashMessage = new FlashMessage($message, $headline, FlashMessage::ERROR);
        $messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($flashMessage);
    }

    /**
     * Add flash message if tsconfig is not set.
     */
    protected function addNoTsConfigSetInfo()
    {
        $headline = LocalizationUtility::translate('config_dir_not_set.headline', 'pagetemplates');
        $message = sprintf(
            LocalizationUtility::translate('config_dir_not_set.message', 'pagetemplates'),
            htmlspecialchars(str_replace(PATH_site, '', $this->configPath), ENT_QUOTES | ENT_HTML5)
        );
        $flashMessage = new FlashMessage($message, $headline, FlashMessage::INFO);
        $messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($flashMessage);
    }

    /**
     * Add flash message if no page is selected.
     */
    protected function addSelectPageInfo()
    {
        $headline = LocalizationUtility::translate('no_page_selected.headline', 'pagetemplates');
        $message = sprintf(
            LocalizationUtility::translate('no_page_selected.message', 'pagetemplates'),
            htmlspecialchars(str_replace(PATH_site, '', $this->configPath), ENT_QUOTES | ENT_HTML5)
        );
        $flashMessage = new FlashMessage($message, $headline, FlashMessage::INFO);
        $messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($flashMessage);
    }

    /**
     * Initialize action
     * fetches storage path from TSConfig
     */
    protected function initializeAction()
    {
        parent::initializeAction();

        $id = (int)$_GET['id'];
        $pagesTSconfig = BackendUtility::getPagesTSconfig($id);
        $this->configPath = rtrim(
            GeneralUtility::getFileAbsFileName($pagesTSconfig['mod.'][self::MODULE_NAME . '.']['storagePath']),
            '/'
        );

        if ($id === 0) {
            $this->addSelectPageInfo();
        } elseif (empty($this->configPath)) {
            $this->addNoTsConfigSetInfo();
        } elseif (!is_dir($this->configPath)) {
            $this->addNoConfigFoundError();
        }
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->templateProvider = $this->objectManager->get(TemplateProvider::class, $this->configPath);
    }

    /**
     * Display available templates.
     */
    public function indexAction(): void
    {
        $templates = $this->templateProvider->getTemplates();
        $this->view->assign('templates', $templates);
    }

    /**
     * Display the edit form for the chosen template.
     *
     * @param string $templateIdentifier
     */
    public function createAction(string $templateIdentifier): void
    {
        try {
            $configuration = $this->templateProvider->getTemplateConfiguration($templateIdentifier);
            $forms = $this->objectManager->get(FormEngineService::class)->createEditForm($configuration);
            $this->view->assign('forms', $forms);
        } catch (\InvalidArgumentException $e) {
            if ($e->getCode() === 1483357769811) {
                $headline = LocalizationUtility::translate('exception.1483357769811.headline', 'pagetemplates');
                $message = sprintf(
                    LocalizationUtility::translate('exception.1483357769811.message', 'pagetemplates'),
                    htmlspecialchars($templateIdentifier, ENT_QUOTES | ENT_HTML5),
                    htmlspecialchars(str_replace(PATH_site, '', $this->configPath), ENT_QUOTES | ENT_HTML5)
                );
                $flashMessage = new FlashMessage($message, $headline, FlashMessage::ERROR);
                $messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
                $messageQueue->addMessage($flashMessage);
            } else {
                throw $e;
            }
        }
    }

    /**
     * save template as new page
     * and send the user to the page module.
     */
    public function saveNewPageAction(): void
    {
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $data = $_POST['data'];
        // sort data to get the same order as when entering it
        foreach ($data as $table => &$elements) {
            arsort($elements);
        }
        unset($elements);
        $newPageIdentifier = key($data['pages']);
        $tce->start($data, []);
        $tce->process_datamap();
        BackendUtility::setUpdateSignal('updatePageTree');
        $realPid = $tce->substNEWwithIDs[$newPageIdentifier];

        $pageModuleUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
            'web_layout',
            ['id' => $realPid]
        );

        $this->redirectToUri($pageModuleUrl);
    }
}
