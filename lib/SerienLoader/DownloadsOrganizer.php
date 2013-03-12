<?php

namespace SerienLoader;

use Webforge\Common\System\File;
use Webforge\Common\System\Dir;
use Psc\System\Logger;
use Psc\System\EchoLogger;
use SerienLoader\Entities\Episode;

/**
 * Organizer der auf dem Client die Episoden verschiebt / runterlädt / mit Subs ergänzt usw
 *
 * Dies ist das Kernstück des Teils des Serien-Loaders welches auf der Zbox ausgeführt wird
 */
class DownloadsOrganizer extends \Psc\Object {
  
  protected $client;
  
  protected $log = array();
  
  protected $downloadDir;
  
  protected $targetDir;
  
  protected $hosterPrio = array('netload.in', 'share-online.biz','rapidshare.com');
  
  protected $jdownloader;
  
  protected $subtitlesManager;
  
  protected $logger;
  
  public function __construct(Dir $downloadDir, Dir $targetDir, Client $client, JDownloaderRPC $jdownloader = NULL, SubtitlesManager $subtitlesManager = NULL, Logger $logger = NULL) {
    $this->jdownloader = $jdownloader ?: new JDownloaderRPC();
    $this->client = $client;
    $this->downloadDir = $downloadDir;
    $this->targetDir = $targetDir;
    $this->logger = $logger ?: new EchoLogger();
    $this->subtitlesManager = $subtitlesManager ?: new SubtitlesManager(
      $subs = $this->targetDir->sub('subs/'),
      $cacheFile = $this->downloadDir->getFile('serien-loader-cache/storage.cacheInfo.php'),
      NULL,
      $this->logger
    );
  }
  
  public function organize() {
    $this->log('Starte DownloadsOrganizer...');
    $this->log('');
    $this->log(sprintf('Frage die Episoden von %s ab...',$this->client->getURL()));
    
    $episodes = $this->client->getEpisodes();
    $this->log(count($episodes).' Episode(n) gefunden');
    $this->logger->br();
    if (isset($this->client->getLastResponse()->errors) && ($er = $this->client->getLastResponse()->errors) && count($er)>0) {
      foreach ($er as $episodeInfo => $errors) {
        $this->log('  Fehler bei: '.$episodeInfo.': '.\Psc\A::join($errors,"\n    %s"));
      }
    }
    
    if (count($episodes) == 0) {
      $this->log('Keine Episoden zum überprüfen');
    }
    
    /* diese episoden haben jetzt bestimmte stati, die für den Client wichtig sind.
       scheduled, downloaded, etc.
       
       je nach Status verschieben wir die Folgen
       schauen, ob es sie schon gibt
       starten den Download
       oder ähnliches
    */
    foreach ($episodes as $episode) {
      
      // wir haben genau die zustände: SCHEDULED, DOWNLOADED, MOVED, WAIT_FOR_SUB
      $this->log('Bearbeite Episode: '.$episode->getInfo().' Status: '.$episode->getStatus());
      
      try {
        /* zu allererst schauen wir, ob wir die Episode schon im Zielverzeichnis haben */
        $exists = $this->exists($episode);
        
        if ($exists) {
          $this->log(sprintf('  Episode existiert im Zielverzeichnis. Status -> WAIT_FOR_SUB'));
          $episode->setStatus(Status::WAIT_FOR_SUB);
        }

        if ($episode->getStatus() === Status::SCHEDULED) {
          // wir fügen es dem jdownloader hinzu 
          try {
            $this->startEpisode($episode); // kann hier nach auf DOWNLOADED gesetzt worden sein
          } catch (NotDecryptedException $e) {
            $this->log('  Links decrypten! ('.$e->getMessage().')');
          }
        }
        
        // wir überprüfen, ob der Download möglicherweise schon fertig ist 
        if ($episode->getStatus() === Status::DOWNLOADING) {
          $this->checkDownload($episode);
        }
        
        if ($episode->getStatus() === Status::DOWNLOADED) {
          // wir verschieben die Episode ins Zielverzeichnis
          try {
            $this->moveEpisode($episode);
          } catch (NoEpisodeDirectoryException $e) {
            $this->log('  Kein Downloadverzeichnis mit Dateien gefunden! Zum Neuladen auf Scheduled zurücksetzen. ('.$e->directory.')');
            $this->log('  neuer Status: downloading');
            $episode->setStatus(Status::DOWNLOADING);
          }
        }
        
        if ($episode->getStatus() === Status::MOVED ||
            $episode->getStatus() === Status::WAIT_FOR_SUB
            ) {
          if ($this->downloadSub($episode)) {
            $episode->setStatus(Status::FINISHED);
            $this->log('  Alle Subs vorhanden => FINISHED');
          } else {
            $episode->setStatus(Status::WAIT_FOR_SUB); // falls wir moved haben und dann höher setzen
            $this->log('  Warte weiterhin auf fehlende Subs');
          }
        }
        
        $this->client->updateEpisodeStatus($episode, $episode->getStatus());
        $this->client->updateEpisodeExtension($episode, $episode->getExtension());
        
      } catch (\Psc\Exception $e) {
        $this->log('  CRITICAL ERROR: '.$e->getMessage().' '.$e->getFile().':'.$e->getLine());
      }
      
      $this->log(NULL);
    }
    
    return $episodes;
  }
  
  public function startEpisode($episode) {
    try {
      
      /* wir nennen das Paket standarddisiert um es identifizieren zu können */
      $package = $episode->getPackageName();
      
      $this->log(
        sprintf(
          '  hasPackage: %s / %s',
          $this->jdownloader->hasPackage($package) ? 'true' : 'false',
          $this->jdownloader->hasGrabberPackage($package) ? 'true' : 'false'
        )
      );
      
      /* wir schauen nach, ob der Download den wir hinzufügen wollen schon in der Liste vorhanden ist */
      if (!$this->jdownloader->hasPackage($package) && !$this->jdownloader->hasGrabberPackage($package)) {
  
        /* wir haben den download nicht gefunden, also fügen wir diesen hinzu */
        $this->addLinksFromHosterPrio($episode);
        
        /* Package Starten */
        $this->jdownloader->confirmPackage($package);
        $episode->setStatus(Status::DOWNLOADING);
        
        $this->jdownloader->start();
        
      } elseif ($this->jdownloader->hasGrabberPackage($package)) {
        $this->log('  ist bereits im Grabber -> confirm');
        $this->jdownloader->confirmPackage($package);
        
        $episode->setStatus(Status::DOWNLOADING);
        
      } else {
        /* wir haben den download gefunden, und schauen einfach ob der schon finished ist */
        $episode->setStatus(Status::DOWNLOADING);
      }
      
    } catch (NotDecryptedException $e) {
      throw $e;
    } catch (JDownloaderException $e) {
      $this->log('  abbort processing: '.$e->getMessage());
      $this->log('jdownloader: '.$this->jdownloader->flushLog());
      throw $e;
    } catch (\Exception $e) {
      $this->log('  abbort processing: '.$e->getMessage());
      throw $e;
    }
    
    return $episode;
  }
  
  protected function addLinksFromHosterPrio($episode) {
    $epLinks = $episode->getLinksByHoster();
    
    $linksCount = 0;
    foreach ($this->hosterPrio as $hoster) {
      if (array_key_exists($hoster, $epLinks)) {
        $link = $epLinks[$hoster];
        
        if (mb_strlen($link->getDecrypted()) > 0) {
          $links = array_filter(array_map('trim',explode("\n",$link->getDecrypted())));
          
          $linksCount = count($links);

          if ($linksCount > 0) {
            break;
          
          } else {
            $this->log(
              'Aus dem decrypteten LinksText konnten keine Links extrahiert werden für hoster: '.$hoster.". LinksText: '".$link->getDecrypted()."'"
            );
          }
        }
      }
    }
    
    if ($linksCount == 0) {
      throw new NotDecryptedException('Keine decrypteten Links gefunden für Hoster: '.implode(", ", $this->hosterPrio));
    }
    
    $this->log('  '.$linksCount.' Link(s) gefunden. Füge hinzu.');

    /*
     * Wir extracten / downloaden die Episode in ein neues einzelverzeichnis damit wir
     * eindeutig die extrahieren Verzeichnisse (die ja sehr cryptisch sein können) identifizieren können
     * in diesem verzeichnis müssen wir dann nur nach .avi|.mkv oder so schauen
    */
    $this->jdownloader->addLinks($links, $episode->getPackageName(), $this->downloadDir->sub($episode->getPackageName()));
  }
  
  
  public function checkDownload($episode) {
    try {
      /* wir nennen das Paket standarddisiert um es identifizieren zu können */
      $packageName = $episode->getPackageName();
        
      $this->log('  check download status');
        
      /* wenn der download fertig ist setzen wir den korrekten status */
      if ($this->jdownloader->isFinished($packageName)) {
        $this->log('    download fertig. neuer Status: DOWNLOADED');
        $episode->setStatus(Status::DOWNLOADED);

      } elseif(!$this->jdownloader->hasPackage($packageName)) {
        $this->log('    package nicht mehr vorhanden. Suche Verzeichnis.');
        $packageDir = $this->downloadDir->sub($episode->getPackageName().'/');
        
        if ($packageDir->exists()) {
          $this->log('    sieht so aus als wäre der download fertig. neuer Status: DOWNLOADED');
          $episode->setStatus(Status::DOWNLOADED);
        } else {
          $this->log('    kein Package-Dir gefunden. Restarting! neuer Status: SCHEDULED');
          $episode->setStatus(Status::SCHEDULED);
        }
      } else {
        $package = $this->jdownloader->getPackage($packageName);
        
        if ($package->hasMissingFiles()) {
          $this->log('    package hat missing files. => neuer Status: MISSING_FILES');
          $episode->setStatus(Status::MISSING_FILES);
          
        } else {
          $this->log(
            sprintf(
              '    download noch nicht fertig. %d (%s/%s) Prozent abgeschlossen Warte.',
              $package->getPercent(), $package->getLoaded(), $package->getSize()
            )
          );
        }
      }
    } catch (\Exception $e) {
      $this->log('  abbort checking: '.$e->getMessage());
      throw $e;
    }
  }
  
  public function exists(Episode $episode) {
    $file = $this->getTargetFile($episode);
    return $file->exists();
  }
  
  
  public function moveEpisode($episode) {
    $packageDir = $this->downloadDir->sub($episode->getPackageName().'/');
    $this->log('  Untersuche Verzeichnis: '.$packageDir);
    
    if (!$packageDir->exists()) {
      $e = new NoEpisodeDirectoryException('Verzeichnis: '.$packageDir.' existiert nicht. Kann nichts verschieben!');
      $e->directory = $packageDir;
      throw $e;
    }
    
    /* eigentlich müssen wir ja nur die .avi finden, der rest ist für uns uninteressant */
    $avis = $packageDir->getFiles(array('avi','mkv'), NULL, TRUE);
    if (count($avis) > 1) {
      foreach ($avis as $key=>$avi) {
        if (mb_strpos($avi->getName(),'sample')) {
          $this->log('    Ignoriere: '.$avi);
          unset($avis[$key]);
        }
      }
    }
    
    if (count($avis) == 1) {
      /* Verschieben / Subs / Update */
      $video = current($avis);
      $this->log('  Eine avi/mkv - Datei gefunden: '.$video);
      
      $this->moveEpisodeVideo($episode, $video, $packageDir);
      
    } elseif(count($avis) > 1) {
      $this->log('  Mehrere Avis/mkvs im Verzeichnis gefunden. Das kann ich noch nicht!');
    } else {
      $this->log('  Keine avi/mkvs im Verzeichnis gefunden');
      $this->log('  Cleanup fuer: '.$episode->getStatus().'?');
    }
  }
  
  protected function moveEpisodeVideo($episode, File $video, $packageDir) {
    $targetFile = $this->getTargetFile($episode);
    
    if (!$video->isWriteable() || !$video->isReadable()) {// klappt leider nicht
      $this->log('  Datei wird vermutlich gerade entpackt');
      return;
    }
    
    $this->log('  Verschiebe: '.$video.' nach '.$targetFile.'..');

    /* Ziel erstellen */
    $targetFile->getDirectory()->make(Dir::PARENT | DIR::ASSERT_EXISTS);
    
    if ($video->move($targetFile)) {
      $this->log('  verschoben.');
    
      $episode->setExtension($video->getExtension());
      $episode->setStatus(Status::MOVED);
      $this->cleanup($packageDir);
    }

    return $episode;
  }
  
  public function getTargetFile($episode) {
    $lang = current($episode->getLanguages());
    
    /* Serien/<TvShow.title>/Season x|Staffel x/<packagename>.mkv|avi */
    $targetDir = $this->targetDir->sub(
      $episode->getTvShow()->getTitle().'/'.
      sprintf($lang == 'de' ? 'Staffel %d' : 'Season %d',$episode->getSeason()->getNum()).'/' // Season x oder Staffel x
    );
    $targetFile = new File($targetDir, str_replace(' ','.',$episode->getPackageName().'.'.$episode->getRelease()));
    
    if ($episode->getExtension() !== NULL) {
      $targetFile->setExtension($episode->getExtension());
    } else {
      /* suchen */
      foreach (array('mkv','avi') as $format) {
        if ($targetFile->setExtension($format)->exists())
          break;
      }
    }
    
    return $targetFile;
  }
  
  public function downloadSub($episode) {
    $exists = 0;
    $subTitles = $this->getSubtitles($episode);
    
    $subLanguages = $episode->getSubtitleLanguages();
    if (count($subLanguages) > 0) 
      $this->log('  Suche nach Untertiteln für '.implode(",",$subLanguages).'...');
    foreach ($subLanguages as $lang) {
      if (isset($subTitles->$lang)) {
        try {
          $subTarget = clone $this->getTargetFile($episode);
          $subTarget->setName($subTarget->getName(File::WITHOUT_EXTENSION).'-'.$lang.'.srt');
          $subTarget->getDirectory()->create();
          if (!$subTarget->exists()) {
            $subTarget->writeContents($this->client->downloadSub($subTitles->$lang));
            $this->log('    ['.$lang.'] Sub gespeichert als: '.$subTarget->getName());
          } else {
            $this->log('    ['.$lang.'] Bereits vorhanden: '.$subTarget->getName());
          }
          $exists++;
        
        } catch (ClientRequestException $e) {
          $this->log('    ['.$lang.'] Fehler beim Download: '.$e->getMessage());
        }
      } else {
        $this->log('    ['.$lang.'] Kein Subtitle gefunden');
      }
    }
    
    return $exists >= count($subLanguages);
  }

  public function getSubtitles($episode) {
    /* das hier würde ja direkt an subcentral gehen, wir wollen aber schon die fertig
       heruntergeladenen (und entpackten) Dateien genießen
    */
    //$subFile = $this->subtitlesManager->getSubtitle($episode,$lang);

    return (object) $episode->getSubtitles();
  }
  
  public function cleanup(Dir $dir) {
    // @todo checks ob wir nicht doch was cooles löschen
    
    // @todo vielleicht hier auch aus dem JDownloader rausnehmen?
    $dir->delete();
  }
  
  protected function log($msg) {
    $this->logger->writeln($msg);
    return $this;
  }
  
  public function setHosterPrio(Array $prio) {
    $this->hosterPrio = $prio;
  }
}
?>