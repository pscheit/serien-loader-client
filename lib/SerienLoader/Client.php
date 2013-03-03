<?php

namespace SerienLoader;

use Psc\Config;
use SerienLoader\Entities\Episode;
use Psc\URL\RequestDispatcher;

class Client {
  
  protected $url;
  
  protected $lastRequest;
  
  protected $lastResponse;
  
  /**
   * Wenn ein JSON Rquest zuletzt gemacht wurde und der debug als Schlüssel gesetzt hatte ist dies der Inhalt
   *
   * @var mixed
   */
  protected $debug;
  
  /**
   * @var Psc\URL\RequestDispatcher
   */
  protected $dispatcher;
  
  public function __construct($url, RequestDispatcher $dispatcher = NULL) {
    $this->url = rtrim($url,'/');
    $this->dispatcher = $dispatcher ?: new RequestDispatcher();
  }
  
  /**
   * @return Episode[]
   */
  public function getEpisodes() {
    $episodesJSON = $this->process($this->createRequest('/episodes/'));
    
    $episodes = array();
    foreach ($episodesJSON as $json) {
      $episodes[] = Episode::createFromJSON($json);
    }
    
    return $episodes;
  }
  
  public function downloadSub($subtitleURL) {
    return $this->process($this->createRequest($subtitleURL), 'srt');
  }

  public function updateEpisodeStatus($episode, $status) {
    return $this->process($this->createRequest('/episodes/'.$episode->getIdentifier().'/status', array('status'=>$status)));
  }

  public function updateEpisodeExtension($episode, $extension) {
    return $this->process($this->createRequest('/episodes/'.$episode->getIdentifier().'/extension', array('extension'=>$extension)));
  }


  public function presence() {
    $request = $this->createRequest();
    
    /* TODO: set data irgendwie action = Presence, etc */
  }
  

  protected function process($request, $dataType = 'json') {
    $this->lastRequest = $request;
    
    $response = $this->dispatcher->dispatch($request);
    $rawData = $response->getRaw();

    if (empty($rawData)) {
      throw new ClientRequestException($request->getURL().' gab einen leeren string zurück');
    }
    
    if ($dataType == 'json') {
      $json = $rawData;

      $data = json_decode($json);
      if ($data == FALSE) {
        throw new ClientRequestException($request->getURL().': konnte nicht als json dekodiert werden: '.$json);
      }
    
      if ($data->status == 'ok') {
        
        if (isset($data->debug)) {
          $this->debug = $data->debug;
        }
        $this->lastResponse = $data;
        
        return $data->content;
      } else {
        throw new ClientRequestException('Client-Request: '.$request->getURL().' ist fehlgeschlagen.');
      }
    } else {
      $this->lastResponse = $rawData;
      return $rawData;
    }
  }
  
  protected function createRequest($url, Array $postData = NULL) {
    $req = new \Psc\URL\Request($this->url.'/'.ltrim($url,'/'));
    
    if (isset($postData)) {
      $req->setType('POST');
      $req->setData((object) $postData);
    }
    
    return $req;
  }
  
  public function getURL() {
    return $this->url;
  }
  
  public function getLastResponse() {
    return $this->lastResponse;
  }
}
?>