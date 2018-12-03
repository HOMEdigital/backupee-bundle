<?php

namespace Home\BackupeeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Home\BackupeeBundle\Resources\contao\hooks\Backup;

class BackupController extends Controller
{

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
}
