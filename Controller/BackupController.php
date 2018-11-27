<?php

namespace Home\BackupeeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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

        $rootDir = \System::getContainer()->getParameter('kernel.project_dir');
        $filesDir = '/files/dbBackup';
        $file = $rootDir . $filesDir . '/lastBackup.txt';

        #-- check if dir exists
        if(!is_dir($rootDir . $filesDir)){
            mkdir($rootDir . $filesDir);
        }

        $time = Backup::backupNow();

        if($time){
            $handle = fopen($file, 'w+');
            fwrite($handle, $time);
            fclose($handle);
        }

        return new Response('Done');
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

            $rootDir = \System::getContainer()->getParameter('kernel.project_dir');
            $filesDir = '/files/dbBackup';
            $file = $rootDir . $filesDir . '/lastBackup.txt';

            #-- check if dir exists
            if(!is_dir($rootDir . $filesDir)){
                mkdir($rootDir . $filesDir);
            }

            $data = Backup::backupNowDownload();

            if($data['time']){
                $handle = fopen($file, 'w+');
                fwrite($handle, $data['time']);
                fclose($handle);
            }
            
            return $response = new BinaryFileResponse($data['file'], '200', array(), false,
                ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }

        return new Response('Forbidden', '403');
    }
}
