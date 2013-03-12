<?php

namespace SerienLoader\JsonRpc;

class CurlTransport extends \JsonRpc\Transport\BasicClient {

  protected $username, $password;
  
  public function __construct($username, $password) {
    $this->username = $username;
    $this->password = $password;
  }

  public function send($method, $url, $json, $headers = array()) {
    $headers[] = sprintf('Authorization: Basic %s', base64_encode($this->username.':'.$this->password));
    
    return parent::send($method, $url, $json, $headers);
  }
}
