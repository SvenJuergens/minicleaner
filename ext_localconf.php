<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

/**
 * Registering class to scheduler
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['SvenJuergens\\Minicleaner\\Tasks\\CleanerTask'] = array(
	'extension' => $_EXTKEY,
	'title' => 'Mini Cleaner',
	'description' => 'Löscht Dateien innerhalb dieser Ordner',
	'additionalFields' => 'SvenJuergens\\Minicleaner\\Tasks\\CleanerTaskDirectoryField'

);