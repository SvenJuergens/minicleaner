<?php
defined('TYPO3_MODE') || die();

/**
 * Registering class to scheduler
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\SvenJuergens\Minicleaner\Tasks\CleanerTask::class] = [
    'extension' => $_EXTKEY,
    'title' => 'LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf:minicleaner.name',
    'description' => 'LLL:EXT:minicleaner/Resources/Private/Language/locallang.xlf:minicleaner.description',
    'additionalFields' => \SvenJuergens\Minicleaner\Tasks\CleanerTaskDirectoryField::class
];
