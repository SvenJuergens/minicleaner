<?php
namespace SvenJuergens\Minicleaner\Tasks;

/**
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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Original TASK taken from EXT:reports
 */
class CleanerTaskDirectoryField implements AdditionalFieldProviderInterface
{
    /**
     * Additional fields
     *
     * @var array
     */
    protected $fields = ['
        directoriesToClean,
        advancedMode
    '];

    /**
     * Field prefix.
     *
     * @var string
     */
    protected $fieldPrefix = 'miniCleaner';

    /**
     * BlackList of Directories
     *
     * @var string
     */
    protected $blackList = 'typo3,typo3conf,typo3_src,typo3temp,uploads';

    /**
     *  path to LocallangFile
     */
    protected $LLLPath = 'LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf';

    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param CleanerTask $task The task object being edited. Null when adding a task!
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference
     * to the scheduler backend module
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '',
     * 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $fields = [
            'directoriesToClean' => 'textarea',
            'advancedMode' => 'checkbox',
        ];

        if ((string)$schedulerModule->CMD === 'edit') {
            $taskInfo[$this->getFullFieldName('directoriesToClean')] = $task->getDirectoriesToClean();
            $taskInfo[$this->getFullFieldName('advancedMode')] = $task->isAdvancedMode();
            $checked = $task->isAdvancedMode() === true ? 'checked="checked" ' : '';
        } else {
            $checked = '';
        }
        // build html for additional email field
        $fieldName = $this->getFullFieldName('directoriesToClean');
        $additionalFields = [];
        $additionalFields[$fieldName] = [
            'code' => '<textarea class="form-control" rows="10" cols="75" placeholder="' . $GLOBALS['LANG']->sL($this->LLLPath . ':scheduler.placeholderText') . '" name="tx_scheduler[' . $fieldName . ']">' . htmlspecialchars($taskInfo[$fieldName]) . '</textarea>',
            'label' => $GLOBALS['LANG']->sL($this->LLLPath . ':scheduler.fieldLabel'),
            'cshKey' => '',
            'cshLabel' => $fieldName
        ];
        $fieldNameCheckbox = $this->getFullFieldName('advancedMode');
        $additionalFields[$fieldNameCheckbox] = [
            'code' => '<input type="checkbox" name="tx_scheduler[' . $fieldNameCheckbox . ']" ' . $checked . '  />',
            'label' => $GLOBALS['LANG']->sL($this->LLLPath . ':scheduler.fieldLabelAdvancedMode'),
            'cshKey' => '_MOD_txminicleaner',
            'cshLabel' => $fieldNameCheckbox
        ];
        return $additionalFields;
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference
     * to the scheduler backend module
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validInput = true;
        $directoriesToClean = GeneralUtility::trimExplode(
            LF,
            $submittedData[$this->fieldPrefix . 'DirectoriesToClean'],
            true
        );
        foreach ($directoriesToClean as $path) {
            if (!$this->isValidPath($path, $submittedData)) {
                $validInput = false;
                break;
            }
        }
        if ($validInput === false
            || empty($submittedData[$this->getFullFieldName('directoriesToClean')])
        ) {
            $schedulerModule->addMessage(
                $GLOBALS['LANG']->sL($this->LLLPath . ':error.pathNotValid'),
                FlashMessage::ERROR
            );
            $validInput = false;
        }
        return $validInput;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the scheduler backend module
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        if (!$task instanceof CleanerTask) {
            throw new \InvalidArgumentException(
                'Expected a task of type SvenJuergens\\Minicleaner\\Tasks\\CleanerTask,
                 but got ' . htmlspecialchars(\get_class($task)),
                1295012802
            );
        }
        $task->setDirectoriesToClean($submittedData[$this->getFullFieldName('directoriesToClean')]);
        $task->setAvancedMode($submittedData[$this->getFullFieldName('advancedMode')]);
    }

    /**
     * Constructs the full field name which can be used in HTML markup.
     *
     * @param string $fieldName A raw field name
     * @return string Field name ready to use in HTML markup
     */
    protected function getFullFieldName($fieldName)
    {
        return $this->fieldPrefix . ucfirst($fieldName);
    }

    public function isValidPath($path, $submittedData)
    {
        $path = trim($path, DIRECTORY_SEPARATOR);
        if ($submittedData[$this->getFullFieldName('advancedMode')]) {
            return GeneralUtility::validPathStr($path);
        }

        return $path !== '' && file_exists(PATH_site . $path)
            && GeneralUtility::isAllowedAbsPath(PATH_site . $path)
            && GeneralUtility::validPathStr($path)
            && !GeneralUtility::inList($this->blackList, $path)
        ;
    }
}
