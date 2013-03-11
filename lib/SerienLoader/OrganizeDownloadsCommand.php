<?php

namespace SerienLoader;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    
    Webforge\Common\System\Dir,
    Webforge\Common\System\File
  ;

class OrganizeDownloadsCommand extends \Psc\System\Console\Command {

  protected function configure() {
    $this
      ->setName('organize-downloads')
      ->setDescription(
        'Schaut nach ob es neue Downloads gibt / verschiebt fertige / lädt subs herunter'
      )
      ->setDefinition(array(
        new InputArgument(
          'downloadsDir', InputArgument::REQUIRED,
          'Das lokale Verzeichnis in das JDownload herunterlädt mit \ am Ende'
        ),
        new InputArgument(
          'targetDir', InputArgument::REQUIRED,
          'Das Verzeichnis in dem die anderen schon fertigen Serien liegen mit \ am Ende'
        ),
        new InputArgument(
          'hoster-prio', InputArgument::REQUIRED,
          'Eine mit , getrennte liste von Namen für hoster. Die Reihenfolge ist signifikant'
        ),
        new InputArgument(
          'serienLoaderURL', InputArgument::OPTIONAL,
          'z. B. http://serien-loader.ps-webforge.com (default)',
          'http://serien-loader.ps-webforge.com'
        ),
        new InputArgument(
          'jdownloaderRPC', InputArgument::OPTIONAL,
          'z. B. localhost:10025 (default). Wird der port weggelassen wird immer 10025 genommen',
          'localhost:10025'
        ),
      ))
      ->setHelp(
        'Organisiert die lokalen Downloads.'
      );
  }
  
  protected function execute(InputInterface $input, OutputInterface $output) {
    list ($jdHost, $jdPort) = explode(':',$input->getArgument('jdownloaderRPC'),2);
    if (empty($jdHost)) $jdHost = 'localhost';
    if (empty($jdPort)) $jdPort = 10025;
    
    $organizer = new DownloadsOrganizer(new Dir($input->getArgument('downloadsDir')),
                                        new Dir($input->getArgument('targetDir')),
                                        new Client($input->getArgument('serienLoaderURL')),
                                        new JDownloaderRPC($jdHost, $jdPort)
                                       );
    
    $hosterPrio = array_filter(array_map('trim',explode(",",$input->getArgument('hoster-prio'))));
    $organizer->setHosterPrio($hosterPrio);
    
    $organizer->organize($output);
  }
}
?>