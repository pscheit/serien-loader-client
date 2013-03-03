<?php

namespace SerienLoader;

use Webforge\Common\System\Dir,
    Webforge\Common\System\File,
    SerienLoader\Entities\Episode,
    Psc\DataInput,
    Psc\Data\Storage,
    Psc\Data\PHPStorageDriver,
    Psc\DateTime\DateTime,
    Psc\PSC,
    Psc\Preg,
    Psc\System\BufferLogger,
    Psc\System\Logger
;

class SubtitlesManager extends \Psc\Object {
  
  protected $subs;
  
  protected $cacheInfo;
  protected $shortCache;
  
  protected $storage;
  
  protected $loggedIn = FALSE;
  
  /**
   * @var int in Minuten
   */
  protected $cacheRefreshInterval = 40; // see DateTime diff()
  protected $cacheErrorInterval = 20;
  
  protected $sc;
  
  protected $logger; // wird auch an sc weitergegeben
  
  public function __construct(Dir $subs, File $cacheFile, Subcentral $sc = NULL, Logger $logger = NULL) {
    $this->subs = $subs;  //?: PSC::get(PSC::PATH_HTDOCS)->sub('subs/');
    $this->storage = new Storage(new PHPStorageDriver($cacheFile));
    $this->cacheInfo = $this->storage->init()->getData();
    $this->logger = $logger ?: new BufferLogger();
    $this->sc = $sc ?: new Subcentral($this->logger);
    
    $this->shortCache = new DataInput();
  }
  
  protected function login() {
    if (!$this->loggedIn) {
      $this->sc->login('technoplayer','logf22x3');
      $this->loggedIn = TRUE;
    }
  }
  
  /**
   *
   * subs/<TvShow.Title>/<season.num>/<episode.num>/<episode.release>.srt (von subcentral gespeichert)
   * @return Webforge\Common\System\File|NULL
   */
  public function getSubtitle(Episode $episode, $lang = 'de') {
    
    $local = $this->getLocalSubtitle($episode, $lang); // ist dann zip, rar oder srt
    
    if ($local === NULL) {
      $this->logger->writeln('keine lokalen Dateien gefunden. Suche online.');
      $this->grabSubtitles($episode);
      $local = $this->getLocalSubtitle($episode,$lang);
    }
    
    return $local;
  }
  
  protected function getLocalSubtitle($episode, $lang) {
    $episodesDir = $this->getEpisodesDir($episode);
    $this->logger->writeln('Suche Sub('.$lang.') für: '.$episode.' ');

    // wir holen uns alle releases (in allen Formaten) aus dem Episoden Verzeichnis und suchen uns einen heißen Kandidaten
    $episodeDir = $episodesDir->sub($episode->getNum());
    
    // fastcheck
    if (!$episodeDir->exists()) return NULL;
    
    $subs = array();
    foreach ($episodeDir->getFiles(array('srt','rar','zip')) as $sub) {
      //$this->logger->writeln('   Kandidat: '.$sub);
      // nach language filtern und dabei das release holen
      if (($subRelease = Preg::qmatch($sub->getName(File::WITHOUT_EXTENSION),
                                      '/^(.*)\.'.$lang.'$/i'
                                      )) == NULL) {
        continue;
      }
      
      $subRelease = preg_replace('/-?proper-?/i',NULL,$subRelease);
      
      //$this->logger->writeln('  Sub-Release: '.$subRelease);
      // "IMMERSE" in "IMMERSE-x264 720p" oder so
      
      $this->logger->writeln($subRelease.' in '.$episode->getRelease());
      if (mb_stripos($episode->getRelease(),$subRelease) !== FALSE) {
        $subs[$sub->getExtension()] = $sub;
      }
    }
    
    if (count($subs) === 1) {
      $sub = current($subs);
      $this->logger->writeln('  Lokaler Subtitle: '.$sub.' gefunden');
      return $sub;
    } elseif (count($subs) === 0) {
      $this->logger->writeln('  Kein passenden Subtitle('.$lang.') im Episodenverzeichnis gefunden.');
    } else {
      if (isset($subs['srt'])) {
        return $subs['srt'];
      } else {
        $this->logger->writeln('  WARNING: Mehrere Subtitle im Episodenverzeichnis gefunden: '."\n".implode("\n", $subs).' Nehme erstbesten, weil zu faul');
        return current($subs); // ach verdammt, mir jetzt grad egal! (eigentlich wäre hier .srt auswählen toll
      }
    }
  }
  
  protected function getEpisodesDir(Episode $episode) {
    return $this->subs->sub($episode->getTvShow()->getTitle().'/'.$episode->getSeason()->getNum().'/');
  }

  /**
   * kann auch das leere Verzeichnis zurückgeben
   * Verzeichnis wird nur erstellt, wenn es auch subs zum download gibt
   * es wird alle cacheRefreshInterval - Minuten nach einem neuen gesucht, ansonsten das gecachte ergebnis genommen
   * es werden tvshow + season gecached (da dann sowieso alle subtitles heruntergeladen werden, für alle episoden)
   */
  protected function grabSubtitles(Episode $episode) {
    $this->logger->writeln('starte Grabbing');
    
    $season = $episode->getSeason();
    $tvShow = $episode->getTvShow();
    
    $keys = array($tvShow->getId(),$season->getNum());
    $errorKeys = array('errors',$tvShow->getId(),$season->getNum());
    
    $expirySeason = $this->cacheInfo->get($keys, 0, 0);
    $expiryError = $this->cacheInfo->get($errorKeys, 0, 0);
    $dir = $this->getEpisodesDir($episode);
    
    /*
     * $expiry = $damals + $x Sekunden
     */
    $time = time();
    //$this->logger->writeln('SeasonExpiry: '.date('d.m. H:i',$expirySeason). ' < '.date('d.m. H:i',$time));
    //$this->logger->writeln('ErrorExpiry: '.date('d.m. H:i',$expiryError). ' < '.date('d.m. H:i',$time));
    
    /* wann wollen wir nicht updaten?
       -> wenn season check noch nicht abgelaufen ist
          oder
          wenn error gesetzt ist und noch nicht abgelaufen ist
      =>
      wann wollen wir updaten?
      -> wenn season check abgelaufen ist
         und
         wenn error nicht gesetzt ist oder error abgelaufen ist
    */

    if ($expirySeason < $time && ($expiryError === 0 || $expiryError < $time)) {
      $this->logger->writeln('Cache Einträge sind abgelaufen.');

      try {
        $this->login();
    
        list ($threadLink, $threadId) = $this->findThread($episode);
        $subs = $this->sc->getSubs($threadLink);
        
        if (count($subs) > 0) {
          $dir->make(Dir::PARENT | Dir::ASSERT_EXISTS);  
          // subs ist ein array von [$episodeNum][$language][$format] = $attachmentURL;
          // von subcentral haben leider aber $format immer nur sowas wie "IMMERSE"
          // wir haben aber meist sowas wie x264-IMMERSE.720p (und wollen das auch so als Dateinamen)
          
          // deshalb müssen wir das bei getSubtitle gesondert untersuchen
          $this->sc->downloadSubs($subs, $dir);
        }
      
        $this->cacheInfo->set($keys, ($t = time()+$this->cacheRefreshInterval*60));
        
        $this->logger->writeln('neuer Cache Eintrag bis: '.date('d.m. H:i',$t));
      } catch (SubcentralNoTablesException $e) {
        $this->cacheInfo->set($errorKeys,$t = time()+$this->cacheErrorInterval*60);
        $this->logger->writeln('neuer Cache Eintrag (Error) bis: '.date('d.m. H:i',$t));
        throw new SubcentralException('Wurde sich für '.$e->threadURL.' bedankt?');
      } catch (SubcentralException $e) {
        $this->cacheInfo->set($errorKeys,$t = time()+$this->cacheErrorInterval*60);
        $this->logger->writeln('neuer Cache Eintrag (Error) bis: '.date('d.m. H:i',$t));
        throw $e;
      }
    } else {
      $this->logger->writeln('Cache Einträge vorhanden.');
    }
    $this->logger->writeln('Beende Grabbing');
    
    return $dir;
  }
  
  public function findThread(Episode $episode) {
    $this->login();
    $tvShow = $episode->getTvShow();
    $season = $episode->getSeason();
    
    
    /* Thread Caching (nur im ShortCache nicht persistant) */
    $threadKeys = array('threads',$tvShow->getIdentifier(),$season->getNum());
    if (($thread = $this->shortCache->get($threadKeys,FALSE,FALSE)) === FALSE) {
      /* Board Caching ist nicht relevant, da subcentral.php sich schon selbst cached */
      $boardId = $this->sc->findBoard($tvShow);
        
      $thread = $this->sc->findThread($boardId, $season);
      $this->cacheInfo->set($threadKeys, $thread);
    }
    
    return $thread;
  }
  
  public function persist() {
    $this->storage->persist();
  }
}
?>