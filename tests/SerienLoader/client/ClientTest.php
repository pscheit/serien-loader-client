<?php

namespace SerienLoader;

use Psc\Code\Test\Mock\RequestDispatcherBuilder;
use Psc\URL\Response;
use Psc\URL\Request;
use Psc\URL\HTTP\Header;
use Psc\Net\HTTP\SimpleURL;

class ClientTest extends EpisodesTestCase {
  
  protected $dispatcher;
  
  public function setUp() {
    $this->chainClass = 'SerienLoader\\Client';
    parent::setUp();
    
    $this->dispatcher = new RequestDispatcherBuilder($this);
  }
  
  public function testClientReadsOutTheEpisodesListAndMarshallsIntoRealDoctrineObjects_acceptance() {
    $this->expectResponseFor(
      '/episodes/',
      $this->getEpisodesListResponse()
    );
    
    $this->start();
    $episodes = $this->client->getEpisodes();
    
    $this->assertContainsOnlyInstancesOf('SerienLoader\Entities\Episode', $episodes);
    $this->assertCount(19, $episodes);
  }
  
  protected function start() {
    $this->dispatcherMock = $this->dispatcher->build();
    
    $this->client = new Client('http://mocked.serien-loader-api.ps-webforge.com', $this->dispatcherMock);
  }
  
  protected function expectResponseFor($relativeUrl, $response, $matcher = NULL) {
    $relativeUrl = ltrim($relativeUrl, '/');
    
    $this->dispatcher->expectReturnsResponse(
      $response instanceof Response ? $response : $this->createResponse($response),
      function ($request) use ($relativeUrl) {
        return $request->getUrl() === 'http://mocked.serien-loader-api.ps-webforge.com/'.$relativeUrl;
      }
    );
  }
  
  protected function getEpisodesListResponse() {
    return $this->createResponse(
      $this->getEpisodesList()
    );
  }
  
  protected function createResponse($raw) {
    $response = new Response($raw, new Header(array()));
    
    return $response;
  }
}
?>