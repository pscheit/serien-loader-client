<?php

namespace SerienLoader;

/**
 * @group acceptance
 * phpunit --group acceptance tests\SerienLoader\acceptance\XBMCTest.php
 *
 * local xbmc needs to be running for this!, and remote http must be enabled
 */
class XBMCTest extends \Psc\Code\Test\Base {
  
  public function setUp() {
    parent::setUp();
    
    $this->port = 82;
    $this->xbmc = new XBMC('xbmc', 'geheim', $this->port);
  }
  
  public function testScanTriggering() {
    $perms = $this->xbmc->debug();
    
    $this->assertTrue($perms->UpdateData);
  } 
}