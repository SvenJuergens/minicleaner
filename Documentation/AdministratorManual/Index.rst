.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

Administrator Manual
====================

Target group: **Administrators**

Installation
------------

Just install with Extension Manager and create a new scheduler Task "MiniCleaner".
Set directories to flush, (minicleaner only empty the directory and not remove it) one per line.
You are not allowed to flush this directories directly:
typo3, typo3conf, typo3_src, typo3temp, uploads

*Advanced Mode*:
if this option is enabled, almost all security checks will be disabled. Thus, it is possible to empty any directories on the server, e.g. Server LogFiles or temp files

**Please have in mind, that you could kill your TYPO3 Installation, so please double check your paths !! There is no integrated rollback**


.. figure:: ../Images/schedulerTask.png
	:width: 500px
	:alt: Scheduler Task

	the new task

