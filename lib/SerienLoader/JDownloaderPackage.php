<?php

namespace SerienLoader;

class JDownloaderPackage extends \Psc\Object {
  
  protected $linksTotal;
  protected $percent;
  protected $size;
  protected $loaded;
  protected $name;
  
  public function __construct($name) {
    $this->name = $name;
  }
  
  public static function parse(\DOMElement $package) {
    $jq = new \Psc\JS\jQuery($package);
    
    $package = new static($jq->attr('package_name'));
    $package->setLinksTotal((int) $jq->attr('package_linkstotal'));
    $package->setPercent((float) $jq->attr('package_percent'));
    $package->setSize($jq->attr('package_size'));
    $package->setLoaded($jq->attr('package_loaded'));
    return $package;
  }
}
?>