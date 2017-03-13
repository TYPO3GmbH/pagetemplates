<?php
declare(strict_types=1);

namespace T3G\AgencyPack\Pagetemplates\Controller;

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

use T3G\AgencyPack\Pagetemplates\Provider\TemplateProvider;
use T3G\AgencyPack\Pagetemplates\Service\FormEngineService;
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
    protected function addNoConfigFoundError()
    {
        $headline = LocalizationUtility::translate('config_dir_not_found.headline', 'pagetemplates');
        $message = sprintf(
            LocalizationUtility::translate('config_dir_not_found.message', 'pagetemplates'),
            htmlspecialchars(str_replace(PATH_site, '', $this->configPath))
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
            htmlspecialchars(str_replace(PATH_site, '', $this->configPath))
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
            htmlspecialchars(str_replace(PATH_site, '', $this->configPath))
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
        $this->configPath = rtrim(GeneralUtility::getFileAbsFileName($pagesTSconfig['mod.'][self::MODULE_NAME . '.']['storagePath']), '/');

        if ($id === 0) {
            $this->addSelectPageInfo();
        } elseif (empty($this->configPath)) {
            $this->addNoTsConfigSetInfo();
        } elseif (!is_dir($this->configPath)) {
            $this->addNoConfigFoundError();
        }
        $this->templateProvider = $this->objectManager->get(TemplateProvider::class, $this->configPath);
    }

    /**
     * Display available templates.
     */
    public function indexAction()
    {
        $templates = $this->templateProvider->getTemplates();
        $this->view->assign('templates', $templates);
    }

    /**
     * Display the edit form for the chosen template.
     *
     * @param string $templateIdentifier
     */
    public function createAction(string $templateIdentifier)
    {
        try {
            $configuration = $this->templateProvider->getTemplateConfiguration($templateIdentifier);
            $formEngineService = $this->objectManager->get(FormEngineService::class);
            $forms = $formEngineService->createEditForm($configuration);
            $this->view->assign('forms', $forms);
        } catch (\InvalidArgumentException $e) {
            if ($e->getCode() === 1483357769811) {
                $headline = LocalizationUtility::translate('exception.1483357769811.headline', 'pagetemplates');
                $message = sprintf(
                    LocalizationUtility::translate('exception.1483357769811.message', 'pagetemplates'),
                    htmlspecialchars($templateIdentifier),
                    htmlspecialchars(str_replace(PATH_site, '', $this->configPath))
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
    public function saveNewPageAction()
    {
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $data = $_POST['data'];
        // sort data to get the same order as when entering it
        foreach ($data as $table => &$elements) {
            arsort($elements);
        }
        $newPageIdentifier = key($data['pages']);
        $tce->start($data, []);
        $tce->process_datamap();
        BackendUtility::setUpdateSignal('updatePageTree');
        $realPid = $tce->substNEWwithIDs[$newPageIdentifier];

        $pageModuleUrl = BackendUtility::getModuleUrl('web_layout', ['id' => $realPid]);
        $this->redirectToUri($pageModuleUrl);
    }

}
