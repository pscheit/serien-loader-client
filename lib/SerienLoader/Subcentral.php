<?php

namespace SerienLoader;

use \Psc\URL\Request as URLRequest,
    Symfony\Component\CssSelector\Parser,
    \SerienLoader\Entities\TvShow,
    \SerienLoader\Entities\Season,
    \Psc\XML\Helper as xml,
    \Psc\Preg,
    \Webforge\Common\System\Dir,
    \Webforge\Common\System\File,
    \RarArchive,
    \ZipArchive,
    \Psc\A,
    Psc\System\BufferLogger,
    Psc\System\Logger,
    Psc\Code\Code
  ;
use Psc\JS\jQuery;

class Subcentral extends \Psc\Object {
  
  protected $sessionId;
  
  /**
   * Der Request für die gesammte Session
   * 
   * @var \Psc\URL\Request
   */
  protected $req;
  
  protected $cookieJar;
  
  protected $logger;
  
  public function __construct(Logger $logger = NULL) {
    $this->logger = $logger ?: new BufferLogger();
  }
  
  public function login($username, $password) {
    $this->logger->writeln('Login zu Subcentral');
    $this->req = new URLRequest('http://www.subcentral.de/index.php?form=UserLogin', $this->cookieJar);
    $this->cookieJar = $this->req->getCookieJar();
    $this->req->setType('POST');
    $this->req->setOption(CURLOPT_COOKIESESSION,TRUE); // beginne neue session
    $this->req->setOption(CURLOPT_COOKIEFILE,FALSE);
    
    
    $d = $this->req->getData();
    $d->loginUsername = $username;
    $d->loginPassword = $password;
    $d->useCookies = '1';
    $d->url = '';
    
    $html = $this->req->init()->process();
    
    // check?
    $q1 = new jQuery('div#main p.success', $html);
    $q2 = new jQuery('div#main div..success', $html);
    
    if (count($q1) == 0 && $q2 === 0) {
      //file_put_contents('D:\html.html',$html);
      $this->logger->writeln('Fehler beim Login. div#main p.success nicht gefunden.');
      throw new \Psc\Exception('Kein Login möglich! div#main p.success nicht gefunden. Output ist: '.mb_substr($html,0,800).'...');
    }
    
    $this->sessionId = NULL; // ist nicht mehr in der neuen subcentral
    //Preg::qmatch($html,'/index\.php\?s=(.*)$/');
    
    $this->logger->writeln('Login erfolgreich');
    /* das ist der redirect, den die Seite auch machen würde */
    //$this->req = new URLRequest('http://www.subcentral.de/'.$link);
    //$html = $this->req->init()->process();
    
    return $html;
  }
  
  /**
   * @return list($threadLink, $threadId);
   */
  public function findThread($boardId, Season $season) {
    $this->logger->writeln('Suche Thread für '.$season.' in board: '.$boardId);
    $this->req = new URLRequest('http://www.subcentral.de/index.php?page=Board&boardID='.$boardId, $this->cookieJar);
    $html = $this->req->init()->process();
    
    /* wir holen uns den richtigen Thread aus den "wichtigen Themen" */
    $dom = xml::doc($html);
    $res = xml::query($dom,'#stickiesStatus table td.columnTopic div.topic p');
    $stickies = array(); // das sind nicht alle wegen break!
    foreach ($res as $topicP) {
      $topicP = xml::doc($topicP);
      
      $topicA = A::first(xml::query($topicP,'a'));
      $title = (string) $topicA->nodeValue;
      $stickies[] = $title;
      
      $prefix = xml::query($topicP,'span.prefix strong');
      if (count($prefix) == 1 && $prefix[0]->nodeValue == '[Subs]' && Preg::match($title, '/(Staffel\s*'.$season->getNum().'|S0*'.$season->getNum().')/i') > 0) {
        $link = $topicA->getAttribute('href');
        $threadId = (int) Preg::qmatch($link,'/threadId=([0-9]+)/');
        break;
      }
    }
    
    if (!isset($link)) {
      $pe = new SubcentralException('Pfad nicht gefunden: "#stickiesStatus table td.columnTopic div.topic p a" in '.$this->req->getURL().' '.Code::varInfo($stickies));
      
      $e = new SubcentralException('Thread für Staffel: '.$season->getNum().' für Serie: '.$season->getTvShow()->getTitle().' nicht gefunden',0,$pe);
      $this->logger->writeln($e->getMessage());
      $this->logger->writeln($pe->getMessage());
      throw $e;
    }
    
    $link = 'http://www.subcentral.de/'.$link;
    return array($link,$threadId);
  }
  
  /**
   * 
   * Die Werte des Arrays sind relative links zum attachment
   * @return array schlüssel ebene eins ist sowas wie 1 2 ... für die Episoden ebene 2 sind die formate der Releases (ASAP, IMMERSE, etc...)
   */
  public function getSubs($threadLink) {
    $this->logger->writeln('Lade alle Subs aus '.$threadLink);
    $this->req = new URLRequest($threadLink, $this->cookieJar);
    $html = $this->req->init()->process();
    
    $dom = xml::doc($html);
    $tables = xml::query($dom,'div#main div.threadStarterPost div.messageContent div.messageBody div.baseSC table');
    
    if (count($tables) == 0) {
      $e = new SubcentralNoTablesException('Keine Tables: "div#main div.threadStarterPost div.messageContent div.messageBody div.baseSC table " gefunden in: '.$this->req->getURL().' Wurde sich bedankt?');
      $this->logger->writeln($e->getMessage());
      $e->threadURL = $this->req->getURL();
      throw $e;
    }
    
    $defTable = xml::doc(xml::export(array_shift($tables)));
    $titles = xml::query($defTable, 'td a.sclink');
    
    $tabIds = array();
    /* die Titel der Tables stehen oben in der Box */
    foreach ($titles as $a) {
      $tabIds[Preg::qmatch($a->nodeValue,'/^\s*(.*)\*Klick\*$/i')] = Preg::qmatch($a->getAttribute('id'),'/link(.*)/');
    }
    
    $subs = array();
    foreach ($tables as $table) {
      $subTable = xml::doc(xml::export($table));
      $lang = NULL;
      
      foreach (xml::query($subTable,'tr') as $row) {
        $tds = xml::query(xml::doc(xml::export($row)), 'td');
        if (count($tds) > 0) { 
          $episode = (int) Preg::qmatch($tds[0]->nodeValue,'/^\s*E([0-9]+)/');
          foreach (xml::query(xml::doc(xml::export($row)), 'td a') as $attachment) {
            $subs[$episode][$lang][$this->stringifyFormat($attachment->nodeValue)] = $attachment->getAttribute('href');
          }
        } else { // in der th-row die sprache auswählen
          $usa = xml::query(xml::doc(xml::export($row)), 'th img[src*="flags/usa.png"]');
          $de = xml::query(xml::doc(xml::export($row)), 'th img[src*="flags/de.png"]');
          if (count($de) > 0) {
            $lang = 'de';
          } elseif(count($usa) > 0) {
            $lang = 'en';
          } else {
            $lang = 'en';
          }
        }
      }
    }
    $this->logger->writeln(count($subs).' Subs gefunden');
    return $subs;
  }
  
  public function stringifyFormat($value) {
    $value = trim($value);
    $value = str_replace(array('*','\\','/'),NULL, $value);
    return $value;
  }
  
  /**
   * Nimmt alle verfügbaren Subtitles der Staffel und speichert sie nach Format gruppiert in Unterverzeichnissen
   * nach der Episodennummer im verzeichnis $root
   */
  public function downloadSubs(Array $subs, Dir $root) {
    $this->Logger->writeln('downloade Subs');
    foreach ($subs as $episodeNum => $languages) {
      $episodeDir = $root->clone_()->append($episodeNum.'/');
      $episodeDir->make(Dir::ASSERT_EXISTS | Dir::PARENT);
      
      foreach ($languages as $lang => $formats) {
        foreach ($formats as $format=>$attachment) {
          $srt = new File($episodeDir, $format.'.'.$lang.'.srt');
          if (!$srt->exists()) {
          
            $req = new URLRequest('http://www.subcentral.de/'.$attachment, $this->cookieJar);
          
            $archiveContents = $req->init()->process();
            $res = $req->getResponse();
          
            try {
              $type = $res->getContentType() === 'application/x-rar-compressed' ||
                      File::factory($res->getAttachmentFilename())->getExtension() == 'rar'
                        ? 'rar' : 'zip';
            } catch (\Exception $e) {
              $type = 'rar';
            }
            
            $destination = new File($episodeDir, $format.'.'.$lang.'.'.$type);
            $destination->writeContents($archiveContents);
            
            $destination = $this->tryUnpack($destination);
            
            $this->logger->writeln('Schreibe Datei: '.$destination);
          } else {
            $this->logger->writeln('Sub (srt) existiert schon. Überspringe Download.');
          }
        }
      }
    }
  }
  
  public function tryUnpack(File $archiveFile) {
    try {
      // wir suchen das srt im archive
      if ($archiveFile->getExtension() === 'rar') { // vll geht hier sogar zip?
        $archive = new \Psc\System\SimpleRarArchive($archiveFile);
        $files = $archive->listFiles();
        $srt = NULL;
        $this->logger->writeln('Files im Archiv: '.implode("\n",$files));
        foreach ($files as $file) {
          if (\Psc\String::endsWith($file,'.srt')) {
            $srt = $file;
            break;
          }
        }
        
        if ($srt === NULL)
          throw new Exception('Keine .srt Datei im Archiv: '.$archiveFile.' gefunden');
        
        $destination = clone $archiveFile;
        $destination->setExtension('srt');
        $archive->extractFile($srt, $destination);
        
        if ($destination->exists()) {
          $archiveFile->delete();
          
          return $destination; // alles supi
        }
      }
      
    } catch (\Exception $e) {
      $this->logger->writeln('Fehler beim Versuch zu entpacken: '.$e->getMessage());
    }
    
    return $archiveFile;
  }
  
  public function getBoardIds() {
    $this->logger->writeln('Lade BoardIds');
    $cache = new \Psc\Data\FileCache();
    $ser = $cache->load('boardIds', $loaded);
    if (!$loaded) {
      $this->logger->writeln('BoardIds nicht im Cache, mache URL Request');
      $this->req = new URLRequest('http://www.subcentral.de/index.php?s='.$this->sessionId, $this->cookieJar);
      $html = $this->req->init()->process();

      $boardIds = array();
      $dom = xml::doc($html);
      foreach (xml::query($dom,'#search select[name=boardID] option') as $option) {
        $title = $option->nodeValue;
        $boardIds[mb_strtolower($title)] = (int) $option->getAttribute('value');
      }
      
      $this->logger->writeln(count($boardIds).' BoardIds gefunden. Speichere im Cache');
      $ser = serialize($boardIds);
      $cache->store('boardIds',$ser);
    } else {
      $boardIds = unserialize($ser);
      $this->logger->writeln(count($boardIds).' BoardIds im Cache gefunden');
    }
    
    return $boardIds;
  }
  
    
  public function findBoard(TvShow $tvShow) {
    $this->logger->writeln('Suche Board für '.$tvShow->getTitle());
    $ids = $this->getBoardIds();
    $t = mb_strtolower($tvShow->getTitle());
  
    if (!array_key_exists($t,$ids)) {
      $e = new SubcentralTvShowNotFoundException('TvShow: "'.$tvShow.'" wurde auf Subcentral nicht gefunden! (boardIDs)');
      $this->logger->writeln($e->getMessage());
      throw $e;
    }
  
    return $ids[$t];
  }
}
?>