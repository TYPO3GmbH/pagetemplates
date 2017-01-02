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

    /**
     * Returns form engine forms array for editing the template
     *
     * @param array $configuration
     * @return array
     */
    public function createEditForm(array $configuration) : array
    {
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $formResult = $this->getForm($configuration['page'], 'pages', (int)$_GET['id']);
        $formResultCompiler->mergeResult($formResult['formResult']);
        $forms[] = $formResult;
        unset($configuration['page']);

        foreach ($configuration as $table => $contentElements) {
            foreach ($contentElements as $contentElement) {
                $formResult = $this->getForm($contentElement, $table, 0);
                $formResultCompiler->mergeResult($formResult['formResult']);
                $forms[] = $formResult;
            }
        }

        $forms['js'] = $formResultCompiler->printNeededJSFunctions();

        $formResultCompiler->addCssFiles();
        return $forms;
    }


    /**
     * Renders hidden fields for default data that is not editable in the wizard
     *
     * @param string $table
     * @param array $defaults
     * @param string $newUid
     * @return string
     */
    protected function getHiddenFields(string $table, array $defaults, string $newUid) : string
    {
        $additionalFields = '';
        foreach ($defaults as $field => $default) {
            $additionalFields .= '<input type="hidden" name="data[' . $table . '][' . $newUid . '][' . $field . ']" value="' . $default . '" />';
        }
        return $additionalFields;
    }

    /**
     * Gets form engine form for the specified table, fills rendered fields with default values from configuration,
     * adds headline from configured description and hidden fields for default values.
     * Additionally adds form engine JavaScript and Css
     *
     * @param array $configuration
     * @param string $table
     * @param int $parent
     * @return array
     */
    protected function getForm(array $configuration, string $table, int $parent) : array
    {
        $result['formResult'] = null;
        $result['description'] = '';
        $onCreateEditFields = $configuration['onCreateEditFields'] ?? '';
        $result['description'] = $configuration['description'];
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
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
        $additionalFields = $this->getHiddenFields($table, $fieldsNotYetRendered, $newUid);

        $formResult['html'] .= $additionalFields;
        $result['formResult'] = $formResult;
        $result['readOnlyFields'] = $fieldsNotYetRendered;
        return $result;
    }

    /**
     * Generates a list of fields that aren't rendered as form fields but have default values set
     * --> should then be rendered as hidden fields
     *
     * @param array $fieldsRendered
     * @param array $defaults
     * @return array
     */
    protected function prepareFieldsNotYetRendered(array $fieldsRendered, array $defaults) : array
    {
        foreach ($fieldsRendered as $fieldAlreadyRendered) {
            if (array_key_exists(trim($fieldAlreadyRendered), $defaults)) {
                unset($defaults[$fieldAlreadyRendered]);
            }
        }
        return $defaults;
    }
}
