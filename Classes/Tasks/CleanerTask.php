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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
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
    protected $blockList = 'typo3,typo3conf,typo3_src,typo3temp,uploads';

    /**
     * advancedMode
     *
     * @var bool
     */
    protected $advancedMode = false;

    /**
     *  path to LocallangFile
     */
    public function execute(): bool
    {
        $directories = GeneralUtility::trimExplode(LF, $this->directoriesToClean, true);

        if (\is_array($directories)) {
            foreach ($directories as $key => $directory) {
                if ($this->isValidPath($directory)) {
                    $path = $this->getAbsolutePath($directory);
                    $result = self::flushDirectory($path, true);
                    if ($result === false) {
                        trigger_error(
                            $this->getLanguageService()->sl('LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf:error.couldNotFlushDirectory'),
                            E_USER_DEPRECATED
                        );
                        return false;
                    }
                } else {
                    trigger_error(
                        $this->getLanguageService()->sl('LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf:error.pathNotFound'),
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
        $pathSite = Environment::getPublicPath() . '/';
        return $path !== '' && file_exists($pathSite . $path)
            && GeneralUtility::isAllowedAbsPath($pathSite . $path)
            && GeneralUtility::validPathStr($path)
            && !GeneralUtility::inList($this->blockList, $path)
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
        return Environment::getPublicPath() . '/' . $path;
    }

    /**
     * Flushes a directory by first moving to a temporary resource, and then
     * triggering the remove process. This way directories can be flushed faster
     * to prevent race conditions on concurrent processes accessing the same directory.
     *
     *
     * @param string $directory The directory to be renamed and flushed
     * @param bool $keepOriginalDirectory Whether to only empty the directory and not remove it
     * @return bool Whether the action was successful
     */
    public static function flushDirectory(string $directory, $keepOriginalDirectory = false): bool
    {
        $result = false;

        if (is_link($directory)) {
            // Avoid attempting to rename the symlink see #87367
            $directory = realpath($directory);
        }

        if (is_dir($directory)) {
            $temporaryDirectory = rtrim($directory, '/') . '.' . StringUtility::getUniqueId('remove');
            if (rename($directory, $temporaryDirectory)) {
                if ($keepOriginalDirectory) {
                    GeneralUtility::mkdir($directory);
                }
                clearstatcache();
                $result = GeneralUtility::rmdir($temporaryDirectory, true);
            }
        }

        return $result;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
