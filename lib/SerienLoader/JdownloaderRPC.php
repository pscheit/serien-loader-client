<?php

namespace SerienLoader;

use SerienLoader\Entities\Episode;
use Psc\Object;
use Psc\XML\Helper as xml;
use Psc\URL\RequestDispatcher;
use stdClass;

class JDownloaderRPC extends \Psc\Object {
  
  public $log = array();
  
  protected $port;
  protected $host;
  
  /**
   * der letzte Request / die letzte Response
   */
  protected $request, $response;
  
  /**
   * @var Psc\URL\RequestDispatcher
   */
  protected $dispatcher;
  
  /**
   * Cache für XML Docs von einigen "teuren" responses
   */
  protected $cache;
  
  public function __construct($host = 'localhost', $port = 10025, RequestDispatcher $dispatcher = NULL) {
    $this->host = $host;
    $this->port = $port;
    $this->dispatcher = $dispatcher ?: new RequestDispatcher();
    $this->assertVersion();
    
    $this->cache = new stdClass;
  }
  
  public function assertVersion() {
    $response = (int) $this->send('get/rcversion');
    
    if ($response < 12612) {
      throw new Exception('Version ist: '.$response.' es ist aber >= 12612 erwartet. Es muss Jdownloader BETA benutzt werden.');
    }
    return $response;
  }
  
  /**
   * @return bool
   */
  public function hasPackage($package) {
    $doc = $this->getDownloadsAllList();
    
    $res = xml::query($doc, 'jdownloader packages[package_name="'.$package.'"]');
    return count($res) > 0;
  }
  
  public function getPackage($name) {
    $doc = $this->getDownloadsAllList();
    
    $res = xml::query($doc, 'jdownloader packages[package_name="'.$name.'"]');
    
    if (count($res) === 0) {
      throw new JDownloaderException('Ergebnis für '.'jdownloader packages[package_name="'.$name.'"] ist leer');
    }
    
    return JDownloaderPackage::parse(current($res));
  }
  
  public function hasGrabberPackage($package) {
    $doc = $this->getGrabberList();
    
    $res = xml::query($doc, 'jdownloader packages[package_name="'.$package.'"]');
    return count($res) > 0;
  }
  
  public function isFinished($package) {
    $xmls = $this->send('get/downloads/finished/list');
    
    $res = xml::query(xml::doc($xmls), 'jdownloader packages[package_name="'.$package.'"][package_percent="100,00"]');
    return count($res) > 0;
  }
  
  public function addLinks(Array $links, $packageName, $downloadDir = NULL) {
    $this->waitBusy();
    $this->send('/set/grabber/autoadding/false');
    $this->send('/set/grabber/startafteradding/false');
    
    $url = '/action/add/links/';
    $linklist = rawurlencode(implode("\n",$links));
    $url .= $linklist;
    
    $res = $this->send($url);
    if (mb_strpos($res, 'Link(s) added.') === FALSE) {
      throw new JDownloaderException('Link adding hat nicht geklappt: '.$this->request->getURL().' Response: '.$res);
    }
    
    $this->waitForChecked($links);
    $this->waitBusy();
    
    $this->log('move: '.$this->send('/action/grabber/move/'.urlencode($packageName).'/'.$linklist));
    $this->waitBusy();
    $this->log('downloadDir: '.$this->send('/action/grabber/set/downloaddir/'.rawurlencode($packageName).'/'.rawurlencode($downloadDir)));
    $this->waitBusy();
    $this->clearCachedDoc('grabberList');
    if (!$this->hasGrabberPackage($packageName)) {
      $e = new JDownloaderException(sprintf("Es wurde versucht ein neues Paket (%s) hinzuzufuegen, dies klappte aber nicht\n", $packageName));
      throw $e;
    }
  }
  
  public function start() {
    $this->waitBusy();
    $this->send('/action/start');
  }
  
  public function confirmPackage($package) {
    $this->waitBusy();
    $this->send('action/grabber/confirm/'.rawurlencode($package));
    $this->clearCachedDoc('downloadsList');
    $this->clearCachedDoc('grabberList');
  }
  
  // geht nur für grabber package to grabberpackage
  //public function joinPackages($from, $to) {
  //  $this->waitBusy();
  //  
  //  $output = $this->send('/action/grabber/join/'.rawurlencode($to).'/'.rawurlencode($from));
  //  $this->log(sprintf('joinPackages %s %s: %s',$from,$to,$output));
  //  var_dump($output);
  //  
  //  if (mb_stripos($output, 'error') !== FALSE) {
  //    throw new JDownloaderException('Fehler beim Joinen von Packages: '.$output);
  //  }
  //  $this->clearCachedDoc('grabberList');
  //  $this->clearCachedDoc('downloadsList');
  //}
  
  public function removePackage($packageName) {
    $output = $this->send('action/downloads/remove/'.rawurlencode($packageName));
    $this->clearCachedDoc('downloadsList');
    return $output;
  }
  
  public function confirmLinks() {
    $this->waitBusy();
    $this->send('action/grabber/confirmall');
    $this->clearCachedDoc('downloadsList');
    $this->clearCachedDoc('grabberList');
  }
  
  public function removeLinks() {
    $this->waitBusy();
    $this->send('/action/grabber/removeall');
    $this->clearCachedDoc('grabberList');
  }
  
  protected function waitForChecked(Array $links) {
    $timeout = 20;
    $x = 5;
    sleep(5);
    
    do {
      $xmls = $this->send('/get/grabber/list');
      $doc = xml::doc($xmls);
      
      /*
        wir überprüfen hier nur ob das "unchecked" paket hier verschwindet, denn wir können nicht herausfinden
        wie der link in der XML Liste lautet, den wir hinzugefügt haben - denn dieser wird umgemodelt von JDownloader
      */
      $cnt = count(xml::query($doc, 'jdownloader packages[package_name="Unchecked"]'));
      
      if ($x >= $timeout) {
        throw new JDownloaderException('Timeout von '.$timeout.' Sekunden für link-checking hit.');
      }
      
      $x++;
      sleep(1);
    } while($cnt > 0);
  }

  protected function waitBusy() {
    $x = 0;
    while (($r = $this->send('get/grabber/isbusy')) == 'true') {
      if ($x > 20) {
        throw new JDownloaderException('IsBusy: Timeout hit');
      }
      sleep(1);
      $x++;
    }
  }
  
  /**
   * @return xml::doc()
   */
  protected function getDownloadsAllList() {
    return $this->getCachedDoc('downloadsList', 'get/downloads/all/list');
  }

  /**
   * @return xml::doc()
   */
  protected function getGrabberList() {
    return $this->getCachedDoc('grabberList', 'get/grabber/list');
  }
  
  /**
   * @return xml::doc()
   */
  protected function getCachedDoc($name, $url) {
    if (!isset($this->cache->$name)) {
      $xmls = $this->send($url);
    
      if (empty($xmls)) {
        throw new JDownloaderException('Kein Output von: '.$this->request->getURL());
      }
    
      $this->cache->$name = xml::doc($xmls);
    }
    
    return $this->cache->$name;
  }
  
  protected function clearCachedDoc($name) {
    if (isset($this->cache->$name)) {
      unset($this->cache->$name);
    }
    return $this;
  }
  
  /**
   * @param string $url muss mit anfangen
   * @return string raw
   */
  public function send($command) {
    $this->request = new \Psc\URL\Request('http://'.$this->host.':'.$this->port.'/'.ltrim($command,'/'));
    $this->log('sende: '.$this->request->getURL());
    
    $this->response = $this->dispatcher->dispatch($this->request);
    
    return $this->response->getRaw();
  }
  
  public function addLink($link, $packageName, $downloadDir = NULL) {
    return $this->addLinks(array($link),$packageName, $downloadDir);
  }
  
  public function log($msg) {
    $this->log[] = $msg;
  }
  
  public function getLog() {
    return implode("\n", $this->log)."\n";
  }
  
  public function flushLog() {
    $log = $this->getLog();
    $this->log = array();
    
    return $log;
  }
}
