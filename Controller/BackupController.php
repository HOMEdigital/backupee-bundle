<?php

namespace Home\BackupeeBundle\Controller;

use Contao\Database;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Home\BackupeeBundle\Resources\contao\hooks\Backup;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "backend", "_token_check" = true})
 */
class BackupController extends Controller
{
    /**
     * @Route("/import/script/{fileName}", name="importDbWithScript")
     *
     * @param $fileName
     * @return Response
     */
    public function importDbWithScriptAction($fileName)
    {
        $this->container->get('contao.framework')->initialize();
        $filePath = $this->getDumpFilePath() . '/' . $fileName;

        if(file_exists($filePath)){
            #-- import via php script
            $response = $this->importDbFile($filePath);
        }else{
            $response = "File not found at: " . $filePath;
        }

        return new Response($response);
    }

    /**
     * @Route("/import/{fileName}", name="importDb")
     *
     * @param $fileName
     * @return Response
     */
    public function importDbAction($fileName)
    {
        $this->container->get('contao.framework')->initialize();
        $filePath = $this->getDumpFilePath() . '/' . $fileName;

        if(file_exists($filePath)){
            #-- import via mysql cli
            $response = $this->importDbFileCli($filePath);
        }else{
            $response = "File not found at: " . $filePath;
        }

        return new Response($response);
    }

    /**
     * @Route("/backup/now/script", name="nackupNowForScript")
     *
     * @return Response
     */
    public function backupNowForScriptAction()
    {
        $this->container->get('contao.framework')->initialize();
        $filepath = $this->getDumpFilePath();

        #-- manage dump
        $dumpReturn = Backup::doDump($filepath, 'Dump_'.date("Ymd_His").'_no_tl_log.sql', true);

        if(strpos($dumpReturn, 'ERROR') === false){
            self::writeFile($filepath);
        }

        return new Response('<p>Done ' . time() . ' ' . $dumpReturn. '</p>');
    }

    /**
     * @Route("/backup/now", name="backupNow")
     *
     * @return Response
     */
    public function backupNowAction()
    {
        $this->container->get('contao.framework')->initialize();
        $filepath = $this->getDumpFilePath();

        #-- manage dump
        $dumpReturn = Backup::doDump($filepath, 'Dump_'.date("Ymd_His").'.sql');

        if(strpos($dumpReturn, 'ERROR') === false){
            self::writeFile($filepath);
        }

        return new Response('<p>Done ' . time() . ' ' . $dumpReturn. '</p>');
    }

    /**
     * @Route("/backup/download", name="backupDownload")
     *
     * @return Response
     */
    public function backupNowDownloadAction()
    {
        $this->container->get('contao.framework')->initialize();

        $user = \BackendUser::getInstance();
        $user->authenticate();
        if($user->isAdmin){

            $filepath = $this->getDumpFilePath();

            #-- manage dump
            $dumpReturn = Backup::doDump($filepath, 'Dump_'.date("Ymd_His").'.sql');

            if(strpos($dumpReturn, 'ERROR') === false){
                self::writeFile($filepath);
            }

            return $response = new BinaryFileResponse($dumpReturn, '200', array(), false,
                ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }

        return new Response('Forbidden', '403');
    }

    /**
     * get the path to store the backups in
     */
    public static function getDumpFilePath()
    {
        $rootDir = \System::getContainer()->getParameter('kernel.project_dir');
        $filepath = $rootDir . '/files/dbBackup';

        #-- check if dir exists
        if(!is_dir($filepath)){
            mkdir($filepath);
        }

        return $filepath;
    }

    private static function writeFile($filepath)
    {
        $handle = fopen($filepath . '/lastBackup.txt' , 'w+');
        fwrite($handle, time());
        fclose($handle);
    }

    private function importDbFile($filePath)
    {
        // Connect & select the database
        $db = Database::getInstance();

        // Temporary variable, used to store current query
        $templine = '';

        // Read in entire file
        if(strpos($filePath,'.gz') === false){
            $lines = file($filePath);
        }else{
            $lines = gzfile($filePath);
        }

        $error = '';

        // Loop through each line
        foreach ($lines as $line){
            // Skip it if it's a comment
            if(substr($line, 0, 2) == '--' || $line == ''){
                continue;
            }

            // Add this line to the current segment
            $templine .= $line;

            // If it has a semicolon at the end, it's the end of the query
            if (substr(trim($line), -1, 1) == ';'){
                // Perform the query
                if(!$db->prepare($templine)->execute()){
                    $error .= 'Error performing query "<b>' . $templine . '</b>": ' . $db->error . '<br /><br />';
                }

                // Reset temp variable to empty
                $templine = '';
            }
        }
        return !empty($error)?$error:'Done';
    }

    private function importDbFileCli($filePath)
    {
        $dbHost = \Config::get('dbHost');
        $dbUsername = \Config::get('dbUser');
        $dbPassword = \Config::get('dbPass');
        $dbName = \Config::get('dbDatabase');

        // check file type
        if(strpos($filePath,'.gz') === false){
            $cmd = "(mysql --user=" . $dbUsername . " --password=" . $dbPassword . " --host=" . $dbHost . " " . $dbName . " < " . $filePath . ")";
        }else{
            $cmd = "(zcat " . $filePath . " | mysql --user=" . $dbUsername . " --password=" . $dbPassword . " --host=" . $dbHost . " " . $dbName . ")";
        }

        #-- check if exec-function is available
        if (!function_exists('exec')) {
            return "ERROR: function exec doesn't exist";
        } else if (in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions'))))) {
            return "ERROR: function exec in disable_functions entry";
        } else if (strtolower(ini_get('safe_mode')) == 1) {
            return "ERROR: safe mode is on";
        } else {
            $retVal = NULL;

            $return = system($cmd , $retVal);

            if (strpos($return, 'Got error') === false) {
                return 'Done ' . $return;
            } else {
                return "ERROR: " . $return;
            }
        }
    }
}
