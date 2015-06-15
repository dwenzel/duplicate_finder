<?php
namespace CPSIT\Test\Unit\Domain\Repository;

use TYPO3\CMS\Core\Tests\UnitTestCase;

class CachedHashRepositoryTest extends UnitTestCase {

	/**
	 * Fixture
	 *
	 * @var \CPSIT\DuplicateFinder\Domain\Repository\CachedHashRepository
	 * @coversDefaultClass \CPSIT\DuplicateFinder\Domain\Repository\CachedHashRepository
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock(
			'CPSIT\\DuplicateFinder\\Domain\\Repository\\CachedHashRepository',
			array('dummy'), array(), '', FALSE
		);
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 * @covers ::containsHashForRecord
	 */
	public function containsHashForRecordInitiallyReturnsUncachedResult() {
		$mockHashRepository = $this->getMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
				array('containsHashForRecord'), array(), '', FALSE);
		$this->fixture->_set('hashRepository', $mockHashRepository);
		$tableName = 'foo';
		$uid = 4;

		$mockHashRepository->expects($this->once())->method('containsHashForRecord');
		$this->fixture->containsHashForRecord($tableName, $uid);
	}

	/**
	 * @test
	 * @covers ::containsHashForRecord
	 */
	public function containsHashForRecordReturnsUncachedResultIfRecordIsNotCached() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\CachedHashRepository',
				array('hasIndexTable'), array(), '', FALSE);
		$mockHashRepository = $this->getMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
				array('containsHashForRecord'), array(), '', FALSE);
		$fixture->_set('hashRepository', $mockHashRepository);
		$tableName = 'foo';
		$uid = 4;
		$indexTables = array(
				$tableName => array()
				);
		$fixture->_set('indexTables', $indexTables);

		$fixture->expects($this->once())->method('hasIndexTable')
			->with($tableName)
			->will($this->returnValue(TRUE));
		$mockHashRepository->expects($this->once())->method('containsHashForRecord');
		$fixture->containsHashForRecord($tableName, $uid);
	}

	/**
	 * @test
	 * @covers ::containsHashForRecord
	 */
	public function containsHashForRecordReturnsCachedResultIfRecordCached() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\CachedHashRepository',
				array('hasIndexTable'), array(), '', FALSE);
		$mockHashRepository = $this->getMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
				array('containsHashForRecord'), array(), '', FALSE);
		$fixture->_set('hashRepository', $mockHashRepository);
		$tableName = 'foo';
		$uid = 4;
		$hash = 'bar';
		$indexTables = array(
				$tableName => array($uid => $hash)
				);
		$fixture->_set('indexTables', $indexTables);

		$fixture->expects($this->once())->method('hasIndexTable')
			->with($tableName)
			->will($this->returnValue(TRUE));
		$mockHashRepository->expects($this->never())->method('containsHashForRecord');
		$this->assertTrue(
			$fixture->containsHashForRecord($tableName, $uid)
			);
	}

	/**
	 * @test
	 * @covers ::contains
	 */
	public function containsReturnsUncachedResultIfHashTableDoesNotExist() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\CachedHashRepository',
				array('hasHashTable'), array(), '', FALSE);
		$mockHashRepository = $this->getMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
				array('contains'), array(), '', FALSE);
		$fixture->_set('hashRepository', $mockHashRepository);
		$tableName = 'foo';
		$hash = 'bar';

		$fixture->expects($this->once())->method('hasHashTable')
			->with($tableName)
			->will($this->returnValue(FALSE));
		$mockHashRepository->expects($this->once())->method('contains')
			->with($hash, $tableName)
			->will($this->returnValue(TRUE));
		$this->assertTrue(
			$fixture->contains($hash, $tableName)
			);
	}

	/**
	 * @test
	 * @covers ::contains
	 */
	public function containsReturnsUncachedResultIfHashIsNotInHashTable() {
		$mockHashRepository = $this->getMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
				array('contains'), array(), '', FALSE);
		$this->fixture->_set('hashRepository', $mockHashRepository);
		$tableName = 'foo';
		$hash = 'bar';
		$hashTables = array(
				$tableName => array(
					'baz' => 5
					)
				);
		$this->fixture->_set('hashTables', $hashTables);

		$mockHashRepository->expects($this->once())->method('contains')
			->with($hash, $tableName)
			->will($this->returnValue(TRUE));
		$this->assertTrue(
			$this->fixture->contains($hash, $tableName)
			);
	}

	/**
	 * @test
	 * @covers ::contains
	 */
	public function containsReturnsCachedResultIfHashIsInHashTable() {
		$mockHashRepository = $this->getMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
				array('contains'), array(), '', FALSE);
		$this->fixture->_set('hashRepository', $mockHashRepository);
		$tableName = 'foo';
		$hash = 'bar';
		$hashTable = array(
				$tableName => array(
					$hash => 5
					)
				);
		$this->fixture->_set('hashTables', $hashTable);

		$mockHashRepository->expects($this->never())->method('contains');
		$this->assertTrue(
			$this->fixture->contains($hash, $tableName)
			);
	}

	/**
	 * @test
	 * @covers ::update
	 */
	public function updateCallsUpdateOnHashRepository() {
		$mockHashRepository = $this->getMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
				array('update'), array(), '', FALSE);
		$this->fixture->_set('hashRepository', $mockHashRepository);
		$tableName = 'foo';
		$uid = 3;
		$hash = 'bar';

		$mockHashRepository->expects($this->once())->method('update')
			->with(
					$tableName,
					$uid,
					$hash,
					NULL
					);
		$this->fixture->update($tableName, $uid, $hash);
	}

	/**
	 * @test
	 * @clear
	 */
	public function clearForEmptyStringClearsAllTables() {
		$mockHashRepository = $this->getMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
				array('clear'), array(), '', FALSE);
		$this->fixture->_set('hashRepository', $mockHashRepository);
		$hashTables = array('foo');
		$this->fixture->_set('hashTables', $hashTables);

		$mockHashRepository->expects($this->once())->method('clear')
			->with(NULL);
		$this->fixture->clear();
		$this->assertNull(
				$this->fixture->_get('hashTables')
				);
	}

	/**
	 * @test
	 * @clear
	 */
	public function clearForStringClearsSpecificTable() {
		$mockHashRepository = $this->getMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
				array('clear'), array(), '', FALSE);
		$this->fixture->_set('hashRepository', $mockHashRepository);
		$tableName = 'foo';
		$hashTables = array(
				$tableName => array ('baz'),
				'bar'
				);
		$this->fixture->_set('hashTables', $hashTables);

		$mockHashRepository->expects($this->once())->method('clear')
			->with($tableName);
		$this->fixture->clear($tableName);
		$this->assertSame(
				array('bar'),
				$this->fixture->_get('hashTables')
				);
	}
	
	/**
	 * @test
	 * @covers ::addHashTable
	 */
	public function addHashTableDoesNotAddTableIfItAlreadyExists() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\CachedHashRepository',
				array('hasHashTable'), array(), '', FALSE);
		$tableName = 'foo';
		$hashTables = array();

		$fixture->expects($this->once())->method('hasHashTable')
			->with($tableName)
			->will($this->returnValue(TRUE));

		$fixture->_call('addHashTable', $tableName);

		$this->assertEquals(
				$hashTables,
				$fixture->_get('hashTables')
				);
	}
	
	/**
	 * @test
	 * @covers ::addHashTable
	 */
	public function addHashTableForStringAddsTable() {
		$tableName = 'foo';
		$expectedHashTables = array(
				$tableName => array()
				);
		$this->fixture->_call('addHashTable', $tableName);

		$this->assertSame(
				$expectedHashTables,
				$this->fixture->_get('hashTables')
				);
	}
	
	/**
	 * @test
	 * @covers ::addIndexTable
	 */
	public function addIndexTableDoesNotAddTableIfItAlreadyExists() {
		$fixture = $this->getAccessibleMock(
				'CPSIT\\DuplicateFinder\\Domain\\Repository\\CachedHashRepository',
				array('hasIndexTable'), array(), '', FALSE);
		$tableName = 'foo';
		$indexTables = array();

		$fixture->expects($this->once())->method('hasIndexTable')
			->with($tableName)
			->will($this->returnValue(TRUE));

		$fixture->_call('addIndexTable', $tableName);

		$this->assertEquals(
				$indexTables,
				$fixture->_get('indexTables')
				);
	}
	
	/**
	 * @test
	 * @covers ::addIndexTable
	 */
	public function addIndexTableForStringAddsTable() {
		$tableName = 'foo';
		$expectedIndexTables = array(
				$tableName => array()
				);
		$this->fixture->_call('addIndexTable', $tableName);

		$this->assertSame(
				$expectedIndexTables,
				$this->fixture->_get('indexTables')
				);
	}

	/**
	 * @test
	 * @covers ::add
	 */
	public function addAddsHashTableIfItDoesNotExist() {
		$tableName = 'foo';
		$hash = 'bar';
		$uid = 3;

		$this->fixture->add($hash, $tableName, $uid);

		$this->assertTrue(
				$this->fixture->_call('hasHashTable', $tableName)
				);
	}

	/**
	 * @test
	 * @covers ::add
	 */
	public function addAddsHash() {
		$tableName = 'foo';
		$hash = 'bar';
		$uid = 3;
		$expectedHashTables = array(
				$tableName => array(
					$hash => $uid
					)
				);
		$this->fixture->add($hash, $tableName, $uid);

		$this->assertSame(
				$expectedHashTables,
				$this->fixture->_get('hashTables')
				);
	}

	/**
	 * @test
	 * @covers ::add
	 */
	public function addDoesNotAddIndexIfNoUidGiven() {
		$tableName = 'foo';
		$hash = 'bar';
		$expectedIndexTables = array();
		$this->fixture->add($hash, $tableName);

		$this->assertSame(
				$expectedIndexTables,
				$this->fixture->_get('indexTables')
				);
	}

	/**
	 * @test
	 * @covers ::add
	 */
	public function addAddsIndexTableIfItDoesNotExist() {
		$tableName = 'foo';
		$hash = 'bar';
		$uid = 3;

		$this->fixture->add($hash, $tableName, $uid);

		$this->assertTrue(
				$this->fixture->_call('hasIndexTable', $tableName)
				);
	}

	/**
	 * @test
	 * @covers ::add
	 */
	public function addAddsIndex() {
		$tableName = 'foo';
		$hash = 'bar';
		$uid = 3;
		$expectedIndexTables = array(
				$tableName => array(
					$uid => $hash
					)
				);
		$this->fixture->add($hash, $tableName, $uid);

		$this->assertSame(
				$expectedIndexTables,
				$this->fixture->_get('indexTables')
				);
	}

	/**
	 * @test
	 * @covers ::getUid
	 */
	public function getUidReturnsInitiallyFalse() {
		$hash = 'bar';
		$tableName = 'foo';
		$this->assertFalse(
				$this->fixture->getUid($hash, $tableName)
				);
	}

	/**
	 * @test
	 * @covers ::getUid
	 */
	public function getUidReturnsFalseIfHashIsNotInHashTable() {
		$notExistingHash = 'bar';
		$tableName = 'foo';
		$this->assertFalse(
				$this->fixture->getUid($notExistingHash, $tableName)
				);
	}

	/**
	 * @test
	 * @covers ::getUid
	 */
	public function getUidReturnsUidIfHashIsInHashTable() {
		$hash = 'bar';
		$tableName = 'foo';
		$uid = 5;
		$hashTables = array(
				$tableName => array(
					$hash => $uid
					)
				);
		$this->fixture->_set('hashTables', $hashTables);
		$this->assertSame(
				$uid,
				$this->fixture->getUid($hash, $tableName)
				);
	}
}
