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
$client = new Client($conf['serienLoaderURL']);
$log = NULL;

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
<html lang="de">
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
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="brand" href="/">SerienLoader Client <?php echo $version ?></a>
          <div class="nav-collapse collapse">
            <ul class="nav">
              <li class="active"><a href="/?reload">Episodes</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="container">      
      <p>
        <a href="/?reload" class="btn btn-large" type="button">Reload</a>
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
        
        <div class="collapse" id="progress-log">
          <?php echo nl2br($log) ?>
        </div>
      </p>

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