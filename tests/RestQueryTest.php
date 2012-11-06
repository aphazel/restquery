<?php
require_once '../vendor/autoload.php';
require_once 'PHPUnit/Autoload.php';

use MelonTech\RestQuery\RestQuery as RestQuery;

class RestQueryTest extends PHPUnit_Framework_TestCase {
  protected $query;
  protected $reflection;
  protected $constants;

  protected function setUp() {
    $this->query = new RestQuery();
    $this->reflection = new ReflectionClass($this->query);
    $this->constants = $this->reflection->getConstants();
  }

  public function tearDown() {
    unset($this->query);
    unset($this->reflection);
    unset($this->constants);
  }

  public function testMethodConstants() {
    // Test the bare minimum static HTTP method constants
    $this->assertEquals(RestQuery::METHOD_GET, CURLOPT_HTTPGET);
    $this->assertEquals(RestQuery::METHOD_POST, CURLOPT_POST);
    $this->assertEquals(RestQuery::METHOD_PUT, CURLOPT_PUT);
  }

  /**
   * @depends testMethodConstants
   */
  public function testCountMethods() {
    $expected = 3;

    $count = 0;
    foreach($this->constants as $constant => $value) {
      if(strpos($constant, 'METHOD_')===0)
        $count++;
    }

    $this->assertEquals($expected, $count);
  }

  /**
   * @depends testMethodConstants
   */
  public function testGetSetMethod() {
    foreach($this->constants as $constant => $value) {
      $expected = constant(get_class($this->query).'::'.$constant);
      $this->query->setMethod(constant(get_class($this->query).'::'.$constant));
      $actual = $this->query->getMethod();
      $this->assertEquals($expected, $actual);
    }
  }

  /**
   * @depends testGetSetMethod
   * @depends testCountMethods
   */
  public function testMethodGet() {
    $search = 'Google';

    $this->query->setMethod(RestQuery::METHOD_GET);
    $this->query->setUrl('http://www.google.com');
    $result = $this->query->execute();

    $this->assertTrue((stripos($result, $search)!==FALSE));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testSetProxy() {
    $this->query->setProxy('localhost', 'abc');
  }
}