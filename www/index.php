<?php

namespace SerienLoader;

use Webforge\Common\System\Dir;
use Webforge\Common\System\File;
use Webforge\Setup\ApplicationStorage;
use Psc\System\BufferLogger;

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'bootstrap.php';

$appStorage = new ApplicationStorage('serien-loader');
$configFile = $appStorage->getFile('inc.config.php');

if ($configFile->exists()) {
  require $configFile;
} else {
  $configDistFile = new File(__DIR__.DIRECTORY_SEPARATOR.'inc.config.dist.php');
  $configFile->getDirectory()->create();
  $configDistFile->copy($configFile);
  print 'Bitte unbedingt die Variablen in '.$configFile.' anpassen!';
  exit;
}

if (!$conf['downloadDir'] || !$conf['targetDir']) {
  print 'Bitte unbedingt die Variablen downloadDir und targetDir in '.$configFile.' anpassen!';
  exit;
}

$reload = isset($_GET['reload']);
$update = isset($_GET['update']);
$scan = isset($_GET['scan']);
$client = new Client($conf['serienLoaderURL']);
$log = NULL;
$episodes = array();

if ($reload) {
  $organizer = new DownloadsOrganizer(
    new Dir($conf['downloadDir']),
    new Dir($conf['targetDir']),
    $client,
    new JDownloaderRPC($conf['jdownloader']['host'], $conf['jdownloader']['port']),
    $subtitlesManager = NULL,
    $log = new BufferLogger()
  );
  
  $organizer->setHosterPrio($conf['hosterPrio']);
  $episodes = $organizer->organize();
  
} elseif($update) {
  $root = Dir::factoryTS(__DIR__)->sub('../../../../')->resolvePath();
  
  $finder = new \Symfony\Component\Process\ExecutableFinder();
  $process = \Psc\System\Console\Process::build($finder->find('composer'))
    ->addOption('working-dir', $root)
    ->addOption('prefer-dist')
    ->addOption('v')
    ->addArgument('update')
    ->end();
  
    
  $process->run();
  
  $log = "Running self-update. \n";
  $log .= "run: ".$process->getCommandline()."\n";
  $log .= $process->getOutput()."\n";
  $log .= $process->getErrorOutput()."\n";
  
} elseif($scan) {
  $xbmc = new XBMC($conf['xbmc']['username'], $conf['xbmc']['password'], $conf['xbmc']['port']);
  
  $log = 'Forcing XBMC to scan. Response: ';
  $log .= $xbmc->scan();
  
} else {
  $episodes = $client->getEpisodes();
  $log = 'nothing was updated. Hit reload';
}

$episodesJs = array();
foreach ($episodes as $episode) {
  $episodesJs[] = (object) array(
    'info'=>$episode->getInfo(),
    'status'=>$episode->getStatus()
  );
}
$episodesJs = json_encode($episodesJs);

$version = NULL;
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="de">
  <head>
    <meta charset="utf-8">
    <title>SerienLoader Client</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="http://www.ps-webforge.com/">

    <link href="bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-top: 60px;
        padding-bottom: 40px;
      }
    </style>
    
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
    <![endif]-->
    
    <!--<script src="js/require.min.js"></script>-->
    
    <script src="js/jquery-1.9.1.min.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <script src="js/knockout-2.2.1.js"></script>
  </head>

  <body>
    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="brand" href="/">SerienLoader Client <?php echo $version ?></a>
          <div class="nav-collapse collapse">
            <ul class="nav">
              <li class="<?= $reload ? 'active' : '' ?>"><a href="/?reload">Episodes</a></li>
              <li class="<?= $update ? 'active' : '' ?>"><a href="/?update">Update</a></li>
              <li class="<?= $scan ? 'active' : '' ?>"><a href="/?scan">XBMC Scan</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <?php if ($update || $scan): ?>
      <?php print nl2br($log); ?>
      <?php else: ?>
      <p>
        <a href="/?reload" class="btn btn-large">Reload</a>
      </p>

      <table class="table table-striped">
        <thead>
          <tr>
            <th>Episode</th>
            <th>Status</th>
            <th>&nbsp;</th>
          </tr>
        </thead>
        <tbody data-bind="foreach: episodes">
          <tr>
            <td data-bind="text: info"></td>
            <td data-bind="html: statusLabel()"></td>
            <td>&nbsp;</td>
          </tr>
        </tbody>
      </table>
      
      <p>
        <button type="button" class="btn btn-info" data-toggle="collapse" data-target="#progress-log">progress log</button>
      </p>
        
      <div class="collapse" id="progress-log">
        <?php echo nl2br($log) ?>
      </div>
      <?php endif; ?>
    </div>

  <script type="text/javascript">
    function Episode(row) {
      var that = this;
      
      this.info = row.info;
      this.status = row.status;
      
      this.statusColor = function () {
        // gibt auch .error
        if (that.status === 'wait_for_sub') {
          return 'success';
        } else if (that.status === 'scheduled') {
          return 'warning';
        } else if (that.status === 'downloading') {
          return '';
        } else {
          return '';
        }
      };
      
      this.statusLabel = function () {
        var label = 'default';
        
        if (that.status === 'wait_for_sub') {
          label = 'warning';
        } else if (that.status === 'finished') {
          label = 'success';
        } else if (that.status === 'scheduled') {
          label = 'info';
        } else if (that.status === 'downloading' || that.status === 'downloaded') {
          label = 'inverse';
        }
        
        return '<span class="label label-'+label+'">'+that.status+'</span>';
      }
    };
    
    function EpisodesList(rows) {
      var that = this;
      
      this.episodes = ko.observableArray([]);
      for (var i = 0; i<rows.length; i++) {
        this.episodes.push(
          new Episode(rows[i])
        );
      }
    };
    
    ko.applyBindings(new EpisodesList(<?php echo $episodesJs ?>));
  </script>
  </body>
</html>