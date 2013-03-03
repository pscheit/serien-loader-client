<?php

namespace SerienLoader;

use Webforge\Common\System\Dir;
use Webforge\Common\System\File;

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'bootstrap.php';
require __DIR__.DIRECTORY_SEPARATOR.'inc.config.php';

$reload = isset($_GET['reload']);
$conf['serienLoaderURL'] = 'http://'.$_SERVER['HTTP_HOST'].'/mock';

if ($reload) {

  $organizer = new DownloadsOrganizer(
    new Dir($conf['downloadDir']),
    new Dir($conf['targetDir']),
    new Client($conf['serienLoaderURL']),
    new JDownloaderRPC($conf['jdownloader']['host'], $conf['jdownloader']['port'])
  );
  
  $organizer->setHosterPrio($conf['hosterPrio']);
  $organizer->organize();
}

$episodes = Array(
  (object) array(
    'info'=>'Castle.2009.S05E14.720p.HDTV.x264-DIMENSION',
    'status'=>'scheduled'
  ),
  (object) array(
    'info'=>'How.I.Met.Your.Mother.S08E17.720p.HDTV.264-DIMENSION',
    'status'=>'wait_for_sub'
  ),
  (object) array(
    'info'=>'In.Treatment.S01E20.German.Dubbed.DL.DVDRIP.WS.XviD-TvR',
    'status'=>'downloading'
  )
);

$episodesJs = json_encode($episodes);


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
          <a class="brand" href="/">SerienLoader Client <?= $version ?></a>
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
          <tr data-bind="css: statusColor()">
            <td data-bind="text: info"></td>
            <td data-bind="text: status"></td>
            <td>&nbsp;</td>
          </tr>
        </tbody>
      </table>

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
    
    ko.applyBindings(new EpisodesList(<?= $episodesJs ?>));
  </script>
  </body>
</html>