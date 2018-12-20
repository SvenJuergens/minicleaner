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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class CleanerTask extends AbstractTask
{
    /**
     * directories to clean
     *
     * @var string
     */
    protected $directoriesToClean;

    /**
     * BlackList of Directories
     *
     * @var string
     */
    protected $blackList = 'typo3,typo3conf,typo3_src,typo3temp,uploads';

    /**
     * advancedMode
     *
     * @var bool
     */
    protected $advancedMode = false;

    /**
     *  path to LocallangFile
     */
    protected $LLLPath = 'LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf';

    public function execute()
    {
        $directories = GeneralUtility::trimExplode(LF, $this->directoriesToClean, true);

        if (\is_array($directories)) {
            foreach ($directories as $key => $directory) {
                if ($this->isValidPath($directory)) {
                    $path = $this->getAbsolutePath($directory);
                    $result = GeneralUtility::flushDirectory($path, true);
                    if ($result === false) {
                        trigger_error(
                            $GLOBALS['LANG']->sL($this->LLLPath . ':error.couldNotFlushDirectory'),
                            E_USER_DEPRECATED
                        );
                        return false;
                    }
                } else {
                    trigger_error(
                        $GLOBALS['LANG']->sL($this->LLLPath . ':error.pathNotFound'),
                        E_USER_DEPRECATED
                    );
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Gets the directories to clean.
     *
     * @return string directories to clean.
     */
    public function getDirectoriesToClean()
    {
        return $this->directoriesToClean;
    }

    /**
     * Sets the directories to clean.
     *
     * @param string $directoriesToClean directories to clean.
     */
    public function setDirectoriesToClean($directoriesToClean)
    {
        $this->directoriesToClean = $directoriesToClean;
    }

    /**
     * @return bool
     */
    public function isAdvancedMode()
    {
        return $this->advancedMode;
    }
    /**
     * @param bool $advancedMode
     */
    public function setAvancedMode($advancedMode)
    {
        $this->advancedMode = (bool)$advancedMode;
    }

    public function isValidPath($path)
    {
        $path = trim($path, DIRECTORY_SEPARATOR);
        if ($this->isAdvancedMode()) {
            return GeneralUtility::validPathStr($path);
        }
        return $path !== '' && file_exists(PATH_site . $path)
            && GeneralUtility::isAllowedAbsPath(PATH_site . $path)
            && GeneralUtility::validPathStr($path)
            && !GeneralUtility::inList($this->blackList, $path)
        ;
    }

    public function getAbsolutePath($path)
    {
        if ($this->isAdvancedMode()) {
            return $path;
        }
        if (class_exists(Environment::class)) {
            return Environment::getPublicPath() . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
        }
        return PATH_site . $path;
    }
}
