<?php

namespace SerienLoader\Mock;

class JDownloaderRPCMock extends \SerienLoader\JDownloaderRPC {
  
  protected $packages = array();
  protected $grabberPackages = array();
  
  public function __construct($host, $port) {
  }
  
  public function hasPackage($packageName) {
    return array_key_exists($packageName, $this->packages);
  }

  public function hasGrabberPackage($packageName) {
    return array_key_exists($packageName, $this->grabberPackages);
  }
  
  public function confirmPackage($packageName) {
    $this->packages[$packageName] = 'confirmed';
    return $this;
  }
}
?>