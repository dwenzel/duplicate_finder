<?php
namespace CPSIT\Test\Unit\Service;

use TYPO3\CMS\Core\Tests\UnitTestCase;

class DuplicateFinderServiceTest extends UnitTestCase {

	/**
	 * Fixture
	 *
	 * @var CertificateDescription
	 * @coversDefaultClass \CPSIT\DuplicateFinder\Service\DuplicateFinderService
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock(
			'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
			array('dummy'), array(), '', FALSE
		);
	}

	public function tearDown() {
		unset($this->fixture);
	}

	public function dummyHashFunction($argument) {
		return str_pad('b', self::HASH_MAX_LENGTH, 'a');
	}

	/**
	 * @test
	 * @covers ::getConfiguration
	 */
	public function getConfigurationReturnsInitiallyNull() {
		$this->assertNull(
			$this->fixture->getConfiguration()
		);
	}

	/**
	 * @test
	 * @covers ::setConfiguration
	 */
	public function setConfigurationForArraySetsConfiguration() {
		$configuration = array('foo' => 'bar');
	
		$this->fixture->setConfiguration($configuration);
		$this->assertSame(
			$configuration,
			$this->fixture->getConfiguration()
		);
	}

	/**
	 * @test
	 * @covers ::getHashFunction
	 */
	public function getHashFunctionForStringReturnsStringFromConfiguration() {
		$configuration = array(
				'hash' => array(
					'function' => 'foo'
					)
				);
		$this->fixture->_set('configuration', $configuration);

		$this->assertSame(
				'foo',
				$this->fixture->getHashFunction()
				);
	}

	/**
	 * @test
	 * @covers ::getDuplicateHashFields
	 */
	public function getDuplicateHashFieldsForStringReturnsHashFields() {
		$configuration = array(
				'tables' => array(
					'foo_table' =>array(
						'hashFields' => 'bar,baz'
						)
					)
				);
		$this->fixture->_set('configuration', $configuration);

		$this->assertSame(
				'bar,baz',
				$this->fixture->getDuplicateHashFields('foo_table')
				);
	}

	/**
	 * @test
	 * @covers ::getDuplicateHashFields
	 * @expectedException \CPSIT\DuplicateFinder\Configuration\InvalidConfigurationException
	 * @expectedExceptionCode 1427630639
	 * @expectedExceptionMessage Hash fields for table foo_table are not configured
	 */
	public function getDuplicateHashFieldsForStringThrowsMissingConfigurationException() {
		$invalidConfiguration = array('foo' => 'bar');
		$this->fixture->_set('configuration', $invalidConfiguration);
		$this->fixture->getDuplicateHashFields('foo_table');
	}

	/**
	 * @test
	 * @covers ::getHashFieldsContent
	 */
	public function getHashFieldsContentForStringReturnsEmptyString() {
		$this->assertSame(
				'',
				$this->fixture->_call('getHashFieldsContent', 'foo')
				);
	}

	/**
	 * @test
	 * @covers ::getHashFieldsContent
	 */
	public function getHashFieldsContentForArrayGetsConcatenatedFieldsContent() {
		$contentArray = array(
				'foo' => 'bar',
				'baz' => 'boo'
				);
		$this->assertSame(
				'barboo',
				$this->fixture->_call('getHashFieldsContent', $contentArray)
				);
	}

	/**
	 * @test
	 * @covers ::getHash
	 */
	public function getHashForArrayReturnsHashValue() {
		$fixture = $this->getMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getHashFunction', 'getHashFieldsContent'), array(), '', FALSE);

		$record = array(
				'foo' => 'bar',
				'bar' => 'baz');
		$concatenatedFields = 'barbaz';
		$expectedHashValue = hash('md5', $concatenatedFields);

		$fixture->expects($this->once())->method('getHashFunction')
			->will($this->returnValue('md5'));
		$fixture->expects($this->once())->method('getHashFieldsContent')
			->with($record)
			->will($this->returnValue($concatenatedFields));

		$this->assertSame(
				$expectedHashValue,
				$fixture->getHash($record)
				);
	}

	/**
	 * @test
	 * @covers ::cropHash
	 */
	public function cropHashLimitsHashLength() {
		$fixture = $this->getMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getHashFunction', 'getHashFieldsContent'), array(), '', FALSE);

		$reflectionObject = new \ReflectionClass('CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService');
		$maxLength = $reflectionObject->getConstant('HASH_MAX_LENGTH');
		$tooLongHash = str_pad('barbaz', $maxLength + 1, 'foo');
		
		$this->assertEquals(
				$maxLength,
				strlen($this->fixture->_call('cropHash', $tooLongHash))
					);
	}

	/**
	 * @test
	 * @covers ::getFuzzyHashFunction
	 * @expectedException \CPSIT\DuplicateFinder\Configuration\InvalidConfigurationException
	 * @expectedExceptionCode 1427637255
	 * @expectedExceptionMessage The configured fuzzy hash function bar does not exist
	 */
	public function getFuzzyHashFunctionThrowsInvalidConfigurationExceptionIfFuzzyHashFunctionDoesNotExist() {
		$invalidConfiguration = array(
				'fuzzyHash' => array(
					'function' => 'bar'
					)
				);
		$this->fixture->_set('configuration', $invalidConfiguration);
		$this->fixture->getFuzzyHashFunction();
	}

	/**
	 * @test
	 * @covers ::getFuzzyHashFunction
	 */
	public function getFuzzyHashFunctionForStringReturnsStringFromConfiguration() {
		// we use an existing function in order to avoid exception
		$configuration = array(
				'fuzzyHash' => array(
					'function' => 'hash'
					)
				);
		$this->fixture->_set('configuration', $configuration);

		$this->assertSame(
				'hash',
				$this->fixture->getFuzzyHashFunction()
				);
	}

	/**
	 * @test
	 * @covers ::getFuzzyHash
	 */
	public function getFuzzyHashCallsFuzzyHashFunctionWithHashFieldsContent() {
		$fixture = $this->getMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getFuzzyHashFunction', 'getHashFieldsContent', 'dummy'), array(), '', FALSE);
		$expectedArgument = array('bar');
		$fuzzyHashFieldsContent = 'baz';
		$fuzzyHashFunction = array($fixture, 'dummy');

		$fixture->expects($this->once())->method('getFuzzyHashFunction')
			->will($this->returnValue($fuzzyHashFunction));
		$fixture->expects($this->once())->method('getHashFieldsContent')
			->with($expectedArgument)
			->will($this->returnValue($fuzzyHashFieldsContent));
		$fixture->getFuzzyHash($expectedArgument);
	}

	/**
	 * @test
	 * @covers ::buildQueue
	 */
	public function buildQueueGetsRowsFromDataBase() {
		$mockDataBase = $this->getMock(
				'TYPO3\\CMS\\Core\\Database\\DataBaseConnection',
				array('exec_SELECTgetRows'), array(), '', FALSE);
		$this->fixture->_set('database', $mockDataBase);
		$fieldNames = 'foo,bar';
		$tableName = 'baz';
		$expectedFieldNames = 'uid,foo,bar';
		$expectedWhereClause = 'deleted=0 AND (duplicate_hash_id="" OR duplicate_hash_id=0)';
		$expectedSortField = 'uid';
		$limit = 30;
		$expectedQueue = array('bom');
		$mockDataBase->expects($this->once())->method('exec_SELECTgetRows')
			->with($expectedFieldNames, $tableName, $expectedWhereClause, '', $expectedSortField, $limit)
			->will($this->returnValue($expectedQueue));
		$this->fixture->buildQueue($tableName, $fieldNames, $limit);
		$this->assertSame(
				$expectedQueue,
				$this->fixture->_get('queue')
				);
		
	}

	/**
	 * @test
	 * @covers ::find
	 */
	public function findBuildsQueueWhenDuplicateHashFieldsAreFound() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getDuplicateHashFields', 'buildQueue'), array(), '', FALSE);
		$tableName = 'foo';
		$queueLength = 300;
		$fieldNames = 'bar,baz';

		$fixture->expects($this->once())->method('getDuplicateHashFields')
			->with($tableName)
			->will($this->returnValue($fieldNames));

		$fixture->expects($this->once())->method('buildQueue')
			->with($tableName, $fieldNames, $queueLength);

		$fixture->find($tableName, $queueLength);
	}

	/**
	 * @test
	 * @covers ::find
	 */
	public function findGetsDuplicateHashFields() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getDuplicateHashFields'), array(), '', FALSE);
		$tableName = 'foo';
		$queueLength = 300;
	
		$fixture->expects($this->once())->method('getDuplicateHashFields')
			->with($tableName);

		$fixture->find($tableName, $queueLength);
	}

	/**
	 * @test
	 * @covers ::find
	 */
	public function findDoesNotBuildQueueIfGetDuplicateHashReturnsEmptyString() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getDuplicateHashFields', 'buildQueue'), array(), '', FALSE);
		$tableName = 'foo';
		$queueLength = 300;
	
		$fixture->expects($this->never())->method('buildQueue');

		$fixture->find($tableName, $queueLength);
	}

	/**
	 * @test
	 * @covers ::find
	 */
	public function findDoesNotProcessEmptyQueue() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getDuplicateHashFields', 'getHash'), array(), '', FALSE);
		$tableName = 'foo';
		$queueLength = 300;
	
		$fixture->expects($this->once())->method('getDuplicateHashFields')
			->will($this->returnValue(''));
		$fixture->expects($this->never())->method('getHash');

		$fixture->find($tableName, $queueLength);
	}

	/**
	 * @test
	 * @covers ::find
	 */
	public function findDoesNotComputeFuzzyHashIfNotConfigured() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getDuplicateHashFields', 'buildQueue', 'getHash', 'getFuzzyHash', 'isDuplicate', 'isRecordHashed'), array(), '', FALSE);
		$tableName = 'foo';
		$queueLength = 100; // default value
		$fieldNames = 'bar';
		$record = array(
				'uid' => 1,
				'fooField' => 'fooFieldValue'
				);
		$fixture->_set('configuration', array('fuzzyHash' => 'foo'));
		$fixture->_set('queue', array($record));

		$fixture->expects($this->once())->method('getDuplicateHashFields');
		$fixture->expects($this->once())->method('getHash');
		$fixture->expects($this->never())->method('getFuzzyHash');
		$fixture->expects($this->once())->method('isDuplicate')
			->will($this->returnValue(FALSE));
		$fixture->expects($this->once())->method('isRecordHashed')
			->will($this->returnValue(TRUE));

		$fixture->find($tableName);
	}

	/**
	 * @test
	 * @covers ::find
	 */
	public function findDoesCallGetFuzzyHashIfConfigured() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getDuplicateHashFields', 'buildQueue', 'getHash', 'getFuzzyHash', 'isDuplicate', 'isRecordHashed'), array(), '', FALSE);
		$tableName = 'foo';
		$record = array(
				'uid' => 1,
				'fooField' => 'fooFieldValue'
				);
		$expectedArgument = array(
				'fooField' => 'fooFieldValue'
				);
		$fixture->_set('configuration', array(
					'fuzzyHash' => array(
						'enabled' => 1,
					)));
		$fixture->_set('queue', array($record));

		$fixture->expects($this->once())->method('getDuplicateHashFields');
		$fixture->expects($this->once())->method('getHash');
		$fixture->expects($this->once())->method('getFuzzyHash')
			->with($expectedArgument);
		$fixture->expects($this->once())->method('isDuplicate')
			->will($this->returnValue(FALSE));
		$fixture->expects($this->once())->method('isRecordHashed')
			->will($this->returnValue(TRUE));

		$fixture->find($tableName);
	}

	/**
	 * @test
	 * @covers ::find
	 */
	public function findAddsDuplicateIfRecordIsDuplicate() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getDuplicateHashFields', 'getHash', 'getFuzzyHash', 'isDuplicate', 'addDuplicate', 'isRecordHashed', 'persistDuplicates'), array(), '', FALSE);
		$tableName = 'foo';
		$uid = 15;
		$record = array(
				'uid' => $uid,
				'fooField' => 'fooFieldValue'
				);
		$fixture->_set('queue', array($record));

		$fixture->expects($this->once())->method('getDuplicateHashFields');
		$fixture->expects($this->once())->method('getHash');
		$fixture->expects($this->once())->method('isDuplicate')
			->will($this->returnValue(TRUE));
	
		$fixture->expects($this->once())->method('addDuplicate')
				->with($tableName, $uid);
		
		$fixture->expects($this->once())->method('isRecordHashed')
			->will($this->returnValue(TRUE));
		$fixture->expects($this->once())->method('persistDuplicates');
		$fixture->find($tableName);
	}

	/**
	 * @test
	 * @covers ::find
	 */
	public function findUpdatesHashIfRecordIsNotHashed() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('getDuplicateHashFields', 'getHash', 'isDuplicate', 'isRecordHashed', 'updateHash'), array(), '', FALSE);
		$tableName = 'foo';
		$uid = 15;
		$record = array(
				'uid' => $uid,
				'fooField' => 'fooFieldValue'
				);
		$fixture->_set('queue', array($record));
		$hash = 'fooHash';

		$fixture->expects($this->once())->method('getDuplicateHashFields');
		$fixture->expects($this->once())->method('getHash')
			->will($this->returnValue($hash));
		
		$fixture->expects($this->once())->method('isRecordHashed')
			->will($this->returnValue(FALSE));
		$fixture->expects($this->once())->method('updateHash')
			->with(NULL, $tableName, $uid, $hash, NULL);

		$fixture->find($tableName);
	}

	/**
	 * @test
	 * @covers ::isFuzzyHashingEnabled
	 */
	public function isFuzzyHashingEnabledReturnsInitiallyFalse() {
		$this->assertFalse(
				$this->fixture->isFuzzyHashingEnabled()
				);
	}

	/**
	 * @test
	 * @covers ::isFuzzyHashingEnabled
	 */
	public function isFuzzyHashingEnabledReturnsFalseIfNotConfigured() {
		$configuration = array('fuzzyHash' => 'foo');
		$this->fixture->_set('configuration', $configuration);

		$this->assertFalse(
				$this->fixture->isFuzzyHashingEnabled()
				);
	}

	/**
	 * @test
	 * @covers ::isFuzzyHashingEnabled
	 */
	public function isFuzzyHashingEnabledReturnsValueFromConfiguration() {
		$configuration = array(
				'fuzzyHash' => array(
					'enabled' => TRUE
					)
				);
		$this->fixture->_set('configuration', $configuration);
		$this->assertTrue(
				$this->fixture->isFuzzyHashingEnabled()
				);
	}

	/**
	 * @test
	 * @covers ::isDuplicate
	 */
	public function isDuplicateReturnsInitiallyFalse() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('isHashInDataBase'), array(), '', FALSE);
		$fixture->expects($this->once())->method('isHashInDataBase');

		$this->assertFalse(
				$fixture->isDuplicate('foo', 'bar')
				);
	}

	/**
	 * @test
	 * @covers ::isDuplicate
	 */
	public function isDuplicateReturnsTrueIfHashIsInDataBase() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('isHashInDataBase'), array(), '', FALSE);
		$fixture->expects($this->once())->method('isHashInDataBase')
			->will($this->returnValue(TRUE));

		$this->assertTrue(
				$fixture->isDuplicate('foo', 'bar')
				);
	}

	/**
	 * @test
	 * @covers ::isDuplicate
	 */
	public function isDuplicateLooksUpDataBaseIfHashIsNotInHashTable() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('isHashInDataBase'), array(), '', FALSE);
		$tableName = 'bar';
		$fixture->_set(
				'hashTables',
				array(
					$tableName => array(
						'boo'
						)
					)
				);

		$fixture->expects($this->once())->method('isHashInDataBase')
			->will($this->returnValue('baz'));

		$this->assertSame(
				'baz',
				$fixture->isDuplicate('foo', $tableName)
				);
	}

	/**
	 * @test
	 * @covers ::isDuplicate
	 */
	public function isDuplicateReturnsTrueIfHashIsInHashTable() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('isHashInDataBase'), array(), '', FALSE);
		$tableName = 'bar';
		$hashValue = 'foo';
		$fixture->_set(
				'hashTables',
				array(
					$tableName => array(
						$hashValue => 'baz'
						)
					)
				);

		/*$fixture->expects($this->once())->method('isHashInDataBase')
			->will($this->returnValue(TRUE));
*/
		$this->assertTrue(
				$fixture->isDuplicate($hashValue, $tableName)
				);
	}

	/**
	 * @test
	 * @covers ::isDuplicate
	 */
	public function isDuplicateDoesNotLookupDataBaseIfHashIsInHashTable() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Service\\DuplicateFinderService',
				array('isHashInDataBase'), array(), '', FALSE);
		$tableName = 'bar';
		$hashValue = 'foo';
		$fixture->_set(
				'hashTables',
				array(
					$tableName => array(
						$hashValue => 'baz'
						)
					)
				);

		$fixture->expects($this->never())->method('isHashInDataBase');

		$fixture->isDuplicate($hashValue, $tableName);
	}
}

