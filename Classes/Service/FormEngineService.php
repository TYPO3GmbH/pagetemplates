<?php
declare(strict_types = 1);


namespace T3G\Pagetemplates\Service;


use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FormEngineService
{
    protected $newPageUid = '';

    public function createEditForm(array $configuration)
    {
        $html = '';

        /** @var FormResultCompiler $formResultCompiler */
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $formResult = $this->getForm($configuration['page'], 'pages', (int)$_GET['id']);
        $formResultCompiler->mergeResult($formResult);
        $html .= $formResult['html'];
        if (array_key_exists('tt_content', $configuration)) {
            foreach ($configuration['tt_content'] as $contentElement) {
                $formResult = $this->getForm($contentElement, 'tt_content', 0);
                $formResultCompiler->mergeResult($formResult);
                $html .= $formResult['html'];
            }
        }
        if ($html !== '') {
            $html = $formResultCompiler->addCssFiles()
                    . $html
                    . $formResultCompiler->printNeededJSFunctions();
        }
        return $html;
    }


    /**
     * @param $table
     * @param $defaults
     * @param $newUid
     * @return string
     */
    protected function getAdditionalFields($table, $defaults, $newUid)
    {
        $additionalFields = '';
        foreach ($defaults as $field => $default) {
            $additionalFields .= '<input type="hidden" name="data[' . $table . '][' . $newUid . '][' . $field . ']" value="' . $default . '" />';
        }
        return $additionalFields;
    }

    /**
     * @param $configuration
     * @return string
     */
    protected function getForm(array $configuration, $table, $parent)
    {
        $onCreateEditFields = $configuration['onCreateEditFields'] ?? '';
        if ($onCreateEditFields !== '') {
            $headline = '<h2>' . $configuration['description'] . '</h2>';
        } else {
            $headline = '';
        }
        /** @var NodeFactory $nodeFactory */
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

        /** @var TcaDatabaseRecord $formDataGroup */
        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        /** @var FormDataCompiler $formDataCompiler */
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

        $defaults = $configuration['defaults'];
        $formDataCompilerInput = [
            'vanillaUid' => $parent,
            'tableName' => $table,
            'command' => 'new',
            'databaseRow' => $defaults,
        ];

        $formData = $formDataCompiler->compile($formDataCompilerInput);
        $formData['fieldListToRender'] = $onCreateEditFields;

        $formData['renderType'] = 'listOfFieldsContainer';
        $formResult = $nodeFactory->create($formData)->render();
        $fieldsRendered = explode(',', $onCreateEditFields);

        $newUid = $formData['databaseRow']['uid'];
        if ($table === 'pages') {
            $this->newPageUid = $formData['databaseRow']['uid'];
            $defaults['pid'] = $parent;
        } else {
            $defaults['pid'] = $this->newPageUid;
        }
        $fieldsNotYetRendered = $this->prepareFieldsNotYetRendered($fieldsRendered, $defaults);
        $additionalFields = $this->getAdditionalFields($table, $fieldsNotYetRendered, $newUid);

        $formResult['html'] = $headline . $formResult['html'] . $additionalFields;
        return $formResult;
    }

    /**
     * @param array $fieldsRendered
     * @param array $defaults
     * @return array
     */
    protected function prepareFieldsNotYetRendered(array $fieldsRendered, array $defaults)
    {
        foreach ($fieldsRendered as $fieldAlreadyRendered) {
            if (array_key_exists(trim($fieldAlreadyRendered), $defaults)) {
                unset($defaults[$fieldAlreadyRendered]);
            }
        }
        return $defaults;
    }

}
