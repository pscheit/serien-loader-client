<?php

namespace SerienLoader;

use JsonRpc\Client AS JsonRpcClient;

class XBMC {
  
  protected $rpc;
  
  public function __construct($username, $password, $port = '82') {
    $this->rpc = new JsonRpcClient(
                  'http://localhost:'.$port.'/jsonrpc',
                  new \SerienLoader\JsonRpc\CurlTransport($username, $password)
                );
  }
  
  public function scan() {
    return $this->call('VideoLibrary.Scan', array());
  }
  
  public function debug() {
    return $this->call('JSONRPC.Permission', array());
  }
  
  protected function call($name, Array $params) {
    $success = $this->rpc->call($name, $params);
    
    if (!$success) {
      throw new Exception(sprintf('JsonRpc Client Error: %s (%s)', $this->rpc->error, $this->rpc->errorCode));
    }
    
    return $this->rpc->result;
  }
}
?>