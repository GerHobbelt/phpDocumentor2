<?php
/**
 * DocBlox
 *
 * @category   DocBlox
 * @package    Tests
 * @copyright  Copyright (c) 2010-2011 Mike van Riel / Naenius. (http://www.naenius.com)
 */

/**
 * TokenIterator mock class.
 *
 * @category   DocBlox
 * @package    Tests
 * @copyright  Copyright (c) 2010-2011 Mike van Riel / Naenius. (http://www.naenius.com)
 */
class TokenIteratorMock extends DocBlox_TokenIterator
{
  public function gotoUpByType()
  {
    $this->gotoTokenByTypeInDirection('{', 'up');
  }

  public function getBrokenTokenIdsOfBracePair()
  {
    return $this->getTokenIdsBetweenPair('}', '{');
  }
}

/**
 * Test class for DocBlox_TokenIterator.
 *
 * @category   DocBlox
 * @package    Tests
 * @copyright  Copyright (c) 2010-2011 Mike van Riel / Naenius. (http://www.naenius.com)
 */
class DocBlox_TokenIteratorTest extends PHPUnit_Framework_TestCase
{
  /** @var array[] tokens returned by token_get_all */
  protected $tokens = array();

  /**
   * @var DocBlox_TokenIterator
   */
  protected $object;

  /**
   * Sets up the fixture.
   *
   * This method is called before a test is executed.
   *
   * @return void
   */
  protected function setUp()
  {
    $this->tokens = token_get_all(file_get_contents(dirname(__FILE__) . '/../../data/TokenIteratorTestFixture.php'));
    $this->object = new DocBlox_TokenIterator($this->tokens);

    $this->object->seek(0);
  }

  /**
   * Tests whether the token iterator is properly constructed and contains only DocBlox_Token objects.
   *
   * @return void
   */
  public function testConstruct()
  {
    $this->assertGreaterThan(0, count($this->object), 'Expected DocBlox_TokenIterator to contain more than 0 items');
    $this->assertEquals(
      count($this->tokens),
      count($this->object),
      'Expected DocBlox_TokenIterator to contain the same amount of items as the output of the tokenizer'
    );

    foreach ($this->object as $token)
    {
      if (!($token instanceof DocBlox_Token))
      {
        $this->fail('All tokens in the DocBlox_TokenIterator are expected to be of type DocBlox_Token, found: '
          . print_r($token, true));
      }
    }

    // test by inserting an array of Tokens ($this->object is effectively an array of objects)
    $test_array = array(
      $this->object[0], $this->object[1], $this->object[2]
    );

    $other_object = new DocBlox_TokenIterator($test_array);
    $this->assertGreaterThan(0, count($other_object), 'Expected DocBlox_TokenIterator to contain more than 0 items');
    $this->assertEquals(
      count($test_array),
      count($other_object),
      'Expected DocBlox_TokenIterator to contain the same amount of items as the given array of Tokens'
    );
  }

  public function testGotoTokenByTypeInDirection()
  {
    $tokens = token_get_all(file_get_contents(dirname(__FILE__) . '/../../data/TokenIteratorTestFixture.php'));
    $mock = new TokenIteratorMock($tokens);

    $this->setExpectedException('InvalidArgumentException');
    $mock->gotoUpByType(); // expect an exception because this stub tries to use a wrong direction
  }

  /**
   * Tests the gotoNextByType method
   */
  public function testGotoNextByType()
  {
    $this->object->seek(0);
    try
    {
      $token = $this->object->gotoNextByType(T_CLASS, -1);
      $this->fail('Expected an InvalidArgumentException when passing a negative number for the max_count argument');
    } catch (InvalidArgumentException $e)
    {
    }

    $this->object->seek(0);
    $token = $this->object->gotoNextByType(T_CLASS, 0);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset');
    $this->assertNotEquals(0, $this->object->key(), 'Expected the key to have a different position');

    $this->object->seek(0);
    $token = $this->object->gotoNextByType(T_CLASS, 40);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset within 40 tokens');
    $this->assertNotEquals(0, $this->object->key(), 'Expected the key to have a different position');

    $this->object->seek(0);
    $token = $this->object->gotoNextByType(T_CLASS, 40, T_REQUIRE);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset within 40 tokens before a T_REQUIRE is encountered');
    $this->assertNotEquals(0, $this->object->key(), 'Expected the key to have a different position');

    $this->object->seek(0);
    $token = $this->object->gotoNextByType(T_CLASS, 40, T_NAMESPACE);
    $this->assertFalse($token, 'Expected to fail finding a T_CLASS in the dataset within 40 tokens before a T_NAMESPACE is encountered');
    $this->assertEquals(0, $this->object->key(), 'Expected the key to be at the starting position');
  }

  /**
   * Tests the gotoNextByType method
   */
  public function testGotoPreviousByType()
  {
    $pos = 40;

    $this->object->seek($pos);
    $token = $this->object->gotoPreviousByType(T_CLASS, 0);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset');
    $this->assertNotEquals($pos, $this->object->key(), 'Expected the key to have a different position');

    $this->object->seek($pos);
    $token = $this->object->gotoPreviousByType(T_CLASS, $pos);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset within '.$pos.' tokens');
    $this->assertNotEquals($pos, $this->object->key(), 'Expected the key to have a different position');

    $this->object->seek($pos);
    $token = $this->object->gotoPreviousByType(T_CLASS, $pos, T_NAMESPACE);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset within '.$pos.' tokens before a T_NAMESPACE is encountered');
    $this->assertNotEquals($pos, $this->object->key(), 'Expected the key to have a different position');

    $this->object->seek($pos);
    $token = $this->object->gotoPreviousByType(T_CLASS, $pos, T_FUNCTION);
    $this->assertFalse($token, 'Expected to fail finding a T_CLASS in the dataset within '.$pos.' tokens before a T_FUNCTION is encountered');
    $this->assertEquals($pos, $this->object->key(), 'Expected the key to be at the starting position');
  }

  public function testFindNextByType()
  {
    $this->object->seek(0);
    try
    {
      $token = $this->object->findNextByType(T_CLASS, -1);
      $this->fail('Expected an InvalidArgumentException when passing a negative number for the max_count argument');
    } catch (InvalidArgumentException $e)
    {
    }

    $this->object->seek(0);
    $token = $this->object->findNextByType(T_CLASS, 0);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset');
    $this->assertEquals(0, $this->object->key(), 'Expected the key to equal the starting position');

    $this->object->seek(0);
    $token = $this->object->findNextByType(T_CLASS, 40);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset within 40 tokens');
    $this->assertEquals(0, $this->object->key(), 'Expected the key to equal the starting position');

    $this->object->seek(0);
    $token = $this->object->findNextByType(T_CLASS, 40, T_REQUIRE);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset within 40 tokens before a T_REQUIRE is encountered');
    $this->assertEquals(0, $this->object->key(), 'Expected the key to equal the starting position');

    $this->object->seek(0);
    $token = $this->object->findNextByType(T_CLASS, 40, T_NAMESPACE);
    $this->assertFalse($token, 'Expected to fail finding a T_CLASS in the dataset within 40 tokens before a T_NAMESPACE is encountered');
    $this->assertEquals(0, $this->object->key(), 'Expected the key to be at the starting position');
  }

  public function testFindPreviousByType()
  {
    $pos = 40;

    $this->object->seek($pos);
    $token = $this->object->findPreviousByType(T_CLASS, 0);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset');
    $this->assertEquals($pos, $this->object->key(), 'Expected the key to have a different position');

    $this->object->seek($pos);
    $token = $this->object->findPreviousByType(T_CLASS, $pos);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset within '.$pos.' tokens');
    $this->assertEquals($pos, $this->object->key(), 'Expected the key to have a different position');

    $this->object->seek($pos);
    $token = $this->object->findPreviousByType(T_CLASS, $pos, T_NAMESPACE);
    $this->assertInstanceOf('DocBlox_Token', $token, 'Expected to find a T_CLASS in the dataset within '.$pos.' tokens before a T_NAMESPACE is encountered');
    $this->assertEquals($pos, $this->object->key(), 'Expected the key to have a different position');

    $this->object->seek($pos);
    $token = $this->object->findPreviousByType(T_CLASS, $pos, T_FUNCTION);
    $this->assertFalse($token, 'Expected to fail finding a T_CLASS in the dataset within '.$pos.' tokens before a T_FUNCTION is encountered');
    $this->assertEquals($pos, $this->object->key(), 'Expected the key to be at the starting position');
  }

  public function testGetBrokenTokenIdsOfBracePair()
  {
    $tokens = token_get_all(file_get_contents(dirname(__FILE__) . '/../../data/TokenIteratorTestFixture.php'));
    $mock = new TokenIteratorMock($tokens);

    // because we have switched the { and } in the stub method it should immediately find a closing brace and thus
    // return null,null
    $mock->seek(0);
    $mock->gotoNextByType(T_CLASS, 0);
    $this->assertEquals(array(null, null), $mock->getBrokenTokenIdsOfBracePair());
  }

  public function testGetTokenIdsOfBracePair()
  {
    $this->object->seek(0);
    $this->object->gotoNextByType(T_CLASS, 0);
    $result = $this->object->getTokenIdsOfBracePair();

    $this->assertInternalType('array', $result, 'Expected result to be an array');
    $this->assertArrayHasKey(0, $result, 'Expected result to have a start element');
    $this->assertArrayHasKey(1, $result, 'Expected result to have an end element');
    $this->assertEquals(33, $result[0], 'Expected the first brace to be at token id 33');
    $this->assertEquals(58, $result[1], 'Expected the closing brace to be at token id 57');
  }

  public function testGetTokenIdsOfParenthesisPair()
  {
    $this->object->seek(0);
    $this->object->gotoNextByType(T_FUNCTION, 0);
    $result = $this->object->getTokenIdsOfParenthesisPair();

    $this->assertInternalType('array', $result, 'Expected result to be an array');
    $this->assertArrayHasKey(0, $result, 'Expected result to have a start element');
    $this->assertArrayHasKey(1, $result, 'Expected result to have an end element');
    $this->assertEquals(40, $result[0], 'Expected the first brace to be at token id 40');
    $this->assertEquals(41, $result[1], 'Expected the closing brace to be at token id 41');
  }
}

?>
