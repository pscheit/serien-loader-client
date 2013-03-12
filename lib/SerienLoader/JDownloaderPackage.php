<?php

namespace SerienLoader;

use Psc\JS\jQuery;

class JDownloaderPackage extends \Psc\Object {
  
  protected $linksTotal;
  protected $percent;
  protected $size;
  protected $loaded;
  protected $name;
  protected $files;
  
  public function __construct($name) {
    $this->name = $name;
    $this->files = array();
  }
  
  public static function parse(\DOMElement $package) {
    $jq = new jQuery($package);
    
    $package = new static($jq->attr('package_name'));
    $package->setLinksTotal((int) $jq->attr('package_linkstotal'));
    $package->setPercent((float) $jq->attr('package_percent'));
    $package->setSize($jq->attr('package_size'));
    $package->setLoaded($jq->attr('package_loaded'));
    
    $files = array();
    foreach ($jq->find('file') as $file) {
      $files[] = JDownloaderFile::parse($file);
    }
    $package->setFiles($files);
    
    return $package;
  }
  
  public function hasMissingFiles() {
    foreach ($this->files as $file) {
      if ($file->isNotFound()) return TRUE;
    }
    
    return FALSE;
  }
}
?>