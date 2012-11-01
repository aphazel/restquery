<?php
namespace MelonTech\RestQuery;

class RestQuery {
  final public const METHOD_POST = CURLOPT_POST;
  final public const METHOD_PUT = CURLOPT_PUT;
  final public const METHOD_GET = CURLOPT_HTTPGET;

  protected $method = NULL;
  protected $postdata = NULL;
  protected $url = NULL;

  protected $curl_handle = NULL;

  public static function validate_url(string $url) {
    return (filter_var($url, FILTER_VALIDATE_URL)!==FALSE);
  }  

  public function setData($data) {
    $this->postdata = $data;
  }

  public function setUrl(string $url) {
    if(!RestQuery::validate_url($url))
      throw new RestQueryException('Invalid URL!');
    $this->url = $url;
    return $this;
  }

  public function setCurlOpt($option, $value) {
    if(empty($this->curl_handle))
      throw new RestQueryException('No valid CURL handle!');
    $result = curl_setopt($this->curl_handle, $option, $value);
    if($result === FALSE) {
      $error = curl_error($this->curl_handle);
      throw new RestQueryException("Error setting CURL option '{$opt}'=>'{$value}': '{$error}'");
    }
    return $this;
  }

  public function setMethod($method) {
    $refl = new ReflectionClass($this);
    $consts = $refl->getConstants();
    $result = array_search($method, $consts);
    if(strpos($result, 'METHOD_')!==0)
      throw new RestQueryException('No matching HTTP method could be found!');
    $this->method = $method;
    return $this;
  }

  public function setCurlOpts(array $curlopts) {
    if(empty($this->curl_handle))
      throw new RestQueryException('No valid CURL handle!');
    foreach($curlopts as $opt => $value) {
        $this->setCurlOpt($opt, $value);
      }
    }
    return $this;
  }
  
  public function execute() {
    if(empty($this->curl_handle))
      throw new RestQueryException('No valid CURL handle!');
    $result = curl_exec($this->curl_handle);
    return $result;
  }
}
