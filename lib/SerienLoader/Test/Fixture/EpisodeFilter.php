<?php

namespace SerienLoader\Fixture;

use Closure;

class EpisodeFilter {
  
  public $closure;
  
  protected $desc;
  
  public function __construct(Closure $filter, $desc = NULL) {
    $this->desc = $desc;
    $this->closure = $filter;
  }
  
  public function __toString() {
    return sprintf('EpisodeFilter: %s', $this->desc ?: 'no description');
  }
}
?>