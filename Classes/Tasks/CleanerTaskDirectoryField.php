<?php

declare(strict_types=1);
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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Original TASK taken from EXT:reports
 */
class CleanerTaskDirectoryField extends AbstractAdditionalFieldProvider
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
    protected $blockList = 'typo3,typo3conf,typo3_src,typo3temp,uploads';

    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param CleanerTask $task The task object being edited. Null when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference
     * to the scheduler backend module
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '',
     * 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        if ((string)$currentSchedulerModuleAction === Action::EDIT) {
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
            'code' => '<textarea class="form-control" rows="10" cols="75" placeholder="' . $this->getLanguageService()->sL('LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf:scheduler.placeholderText') . '" name="tx_scheduler[' . $fieldName . ']">' . htmlspecialchars((string) ($taskInfo[$fieldName] ?? '')) . '</textarea>',
            'label' => 'LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf:scheduler.fieldLabel',
            'cshKey' => '',
            'cshLabel' => $fieldName
        ];
        $fieldNameCheckbox = $this->getFullFieldName('advancedMode');
        $additionalFields[$fieldNameCheckbox] = [
            'code' => '<input type="checkbox" name="tx_scheduler[' . $fieldNameCheckbox . ']" ' . $checked . '  />',
            'label' => 'LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf:scheduler.fieldLabelAdvancedMode',
            'cshKey' => '_MOD_txminicleaner',
            'cshLabel' => $fieldNameCheckbox
        ];
        return $additionalFields;
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $schedulerModule Reference
     * to the scheduler backend module
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
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
            //@extensionScannerIgnoreLine
            $this->addMessage(
                $this->getLanguageService()->sL(
                   'LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf:error.pathNotValid'
               ),
                AbstractMessage::ERROR
            );
            $validInput = false;
        }
        return $validInput;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param AbstractTask $task Reference to the scheduler backend module
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        if (!$task instanceof CleanerTask) {
            throw new \InvalidArgumentException(
                'Expected a task of type SvenJuergens\\Minicleaner\\Tasks\\CleanerTask,
                 but got ' . htmlspecialchars(\get_class($task)),
                1295012802
            );
        }
        $task->setDirectoriesToClean((string)$submittedData[$this->getFullFieldName('directoriesToClean')]);
        $task->setAdvancedMode((bool) ($submittedData[$this->getFullFieldName('advancedMode')] ?? false));
    }

    /**
     * Constructs the full field name which can be used in HTML markup.
     *
     * @param string $fieldName A raw field name
     * @return string Field name ready to use in HTML markup
     */
    protected function getFullFieldName($fieldName): string
    {
        return $this->fieldPrefix . ucfirst($fieldName);
    }

    public function isValidPath($path, $submittedData): bool
    {
        $path = trim($path, DIRECTORY_SEPARATOR);
        if (isset($submittedData[$this->getFullFieldName('advancedMode')])) {
            return GeneralUtility::validPathStr($path);
        }
        $pathSite = Environment::getPublicPath() . '/';
        return $path !== '' && file_exists($pathSite . $path)
            && GeneralUtility::isAllowedAbsPath($pathSite . $path)
            && GeneralUtility::validPathStr($path)
            && !GeneralUtility::inList($this->blockList, $path)
        ;
    }
    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
