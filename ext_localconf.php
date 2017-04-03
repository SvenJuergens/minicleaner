<?php
defined('TYPO3_MODE') or die();

/**
 * Registering class to scheduler
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['SvenJuergens\\Minicleaner\\Tasks\\CleanerTask'] = [
    'extension' => $_EXTKEY,
    'title' => 'Mini Cleaner',
    'description' => 'Löscht Dateien innerhalb dieser Ordner',
    'additionalFields' => 'SvenJuergens\\Minicleaner\\Tasks\\CleanerTaskDirectoryField'

];
