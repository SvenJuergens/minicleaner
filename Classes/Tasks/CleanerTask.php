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


use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanerTask extends AbstractTask
{
    /**
     * directories to clean
     *
     * @var string
     */
    protected $directoriesToClean = null;

    /**
     * BlackList of Diretories
     *
     * @var string
     */
    protected $blackList = 'typo3,typo3conf,t3lib,typo3_src,typo3temp,uploads';


    public function execute()
    {
        $directories = GeneralUtility::trimExplode(LF, $this->directoriesToClean, true);

        if (is_array($directories)) {
            foreach ($directories as $key => $directory) {
                $path = PATH_site . trim($directory, DIRECTORY_SEPARATOR);
                if ($path != PATH_site
                    && file_exists($path)
                    && GeneralUtility::isAllowedAbsPath($path)
                    && GeneralUtility::validPathStr($path)
                    && !GeneralUtility::inList($this->blackList, $path)
                ) {
                    $result = GeneralUtility::flushDirectory($path, true);
                    if ($result === false) {
                        GeneralUtility::devLog($GLOBALS['LANG']->sL('LLL:EXT:minicleaner/locallang.xml:error.couldNotFlushDirectory'), 'minicleaner', 3);
                        return false;
                    }
                } else {
                    GeneralUtility::devLog($GLOBALS['LANG']->sL('LLL:EXT:minicleaner/locallang.xml:error.pathNotFound'), 'minicleaner', 3);
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
     * @return void
     */
    public function setDirectoriesToClean($directoriesToClean)
    {
        $this->directoriesToClean = $directoriesToClean;
    }
}
