<?php
/**
 * Created by PhpStorm.
 * User: felix
 * Date: 10.01.2018
 * Time: 16:48
 */

namespace Home\BackupeeBundle\Resources\contao\hooks;


class Backup
{
    /**
     * will take a backup from DB on initialize after X hours
     * deletes old backups when a specific backup count is reached
     */
    public function backupCheck()
    {
        #-- path to dir and file
        $rootDir = \System::getContainer()->getParameter('kernel.project_dir');
        $filepath = $rootDir . '/files/dbBackup';
        $file = $filepath . '/lastBackup.txt';
        #-- after how many hours the backup will executed again
        $hours = 24;
        #-- how many backup files will be stored
        $stored = 30;

        #-- check if dir exists
        if(!is_dir($filepath)){
            mkdir($filepath);
        }

        #-- check if file exists
        if(!file_exists($file)){
            file_put_contents($file,'');
        }

        #-- check of file exists
        if(!file_get_contents($file)){
            #-- no file or empty take backup now
            $time = self::doDump($filepath, 'Dump_'.date("Ymd_His").'.sql');
        }else{
            #-- check if last backup was X hours ago
            $content = file_get_contents($file);

            if($content <= strtotime('-' . $hours . ' hours')){
                $time = self::doDump($filepath, 'Dump_'.date("Ymd_His").'.sql' );
            }
        }

        #-- write time in file for next check
        if($time){
            $handle = fopen($file, 'w+');
            fwrite($handle, time());
            fclose($handle);
        }

        #-- delete old backups
        exec('ls -d -1tr ' . $rootDir . $filesDir . '/* | head -n -' . ($stored + 1) . ' | xargs -d \'\n\' rm -f');
    }

    /**
     * make a mysqldump and write it in file
     *
     * @param $filepath - the path to write the file in
     * @param $filename - the file to write the dump in
     * @return string
     */
    public static function doDump($filepath, $filename)
    {
        #-- db connection info
        $dbHost = \Config::get('dbHost');
        $dbPort = \Config::get('dbPort');
        $dbUsername = \Config::get('dbUser');
        $dbPassword = \Config::get('dbPass');
        $dbName = \Config::get('dbDatabase');

        #-- make sure if path exists
        if(!is_dir($filepath)){
            mkdir($filepath);
        }
        $file = $filepath . '/' . $dbName . '_' . $filename . '.gz';

        #-- check if exec-function is available
        if (!function_exists('exec')) {
            return "ERROR: function exec doesn't exist";
        } else if (in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions'))))) {
            return "ERROR: function exec in disable_functions entry";
        } else if (strtolower(ini_get('safe_mode')) == 1) {
            return "ERROR: safe mode is on";
        } else {
            $retVal = NULL;
            $dmpExe = "(mysqldump --opt --default-character-set=UTF8 --single-transaction --protocol=TCP --user=" . $dbUsername . " --password=" . $dbPassword . " --host=" . $dbHost . " " . $dbName . " | gzip > " . $file . ")";
            // $return = system($dmpExe ." 2>&1", $retVal); // mit Ausgabe; gibt allerdings auch Warnungen aus. Unschön, wenn eine Warnung kommt und die seite dann darunter steht. Daher auskommentiert
            $return = system($dmpExe , $retVal);

            if (strpos($return, 'Got error') === false) {
                return $file;
            } else {
                return "ERROR: " . $return;
            }

        }
    }
}