<?php
/**
 * Created by PhpStorm.
 * User: felix
 * Date: 10.01.2018
 * Time: 16:46
 */

$GLOBALS['TL_HOOKS']['initializeSystem'][] = array('Home\BackupeeBundle\Resources\contao\hooks\Backup', 'backupCheck');