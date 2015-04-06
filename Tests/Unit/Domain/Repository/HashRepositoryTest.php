<?php
namespace CPSIT\Test\Unit\Domain\Repository;

use TYPO3\CMS\Core\Tests\UnitTestCase;

class HashRepositoryTest extends UnitTestCase {

	/**
	 * Fixture
	 *
	 * @var \CPSIT\DuplicateFinder\Domain\Repository\HashRepository
	 * @coversDefaultClass \CPSIT\DuplicateFinder\Domain\Repository\HashRepository
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock(
			'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
			array('dummy'), array(), '', FALSE
		);
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 * @covers ::__construct
	 */
	public function constructorSetsDataBase() {
		$GLOBALS['TYPO3_DB'] = $this->getMock(
				'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
				array(), array(), '', FALSE);

		$this->fixture->__construct();
		$this->assertSame(
				$GLOBALS['TYPO3_DB'],
				$this->fixture->_get('database')
				);
	}

	/**
	 * @test
	 * @covers ::containsHashForRecord
	 */
	public function containsHashForRecordReturnsFalseForEmptyQueryResult() {
		$mockDatabase = $this->getMock(
				'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
				array('exec_SELECTcountRows'), array(), '', FALSE);
		$this->fixture->_set('database', $mockDatabase);
		$tableName = 'foo';
		$uid = 5;
		$mockDatabase->expects($this->once())->method('exec_SELECTcountRows')
			->with(
					'foreign_uid',
					'tx_duplicatefinder_duplicate_hash',
					'foreign_uid="' . $uid . '" AND foreign_table="' . $tableName . '"')
			->will($this->returnValue(0));

		$this->assertFalse(
				$this->fixture->containsHashForRecord($tableName, $uid)
			);
	}

	/**
	 * @test
	 * @covers ::containsHashForRecord
	 */
	public function containsHashForRecordReturnsTrueForNonEmptyQueryResult() {
		$mockDatabase = $this->getMock(
				'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
				array('exec_SELECTcountRows'), array(), '', FALSE);
		$this->fixture->_set('database', $mockDatabase);
		$tableName = 'foo';
		$uid = 5;
		$mockDatabase->expects($this->once())->method('exec_SELECTcountRows')
			->with(
					'foreign_uid',
					'tx_duplicatefinder_duplicate_hash',
					'foreign_uid="' . $uid . '" AND foreign_table="' . $tableName . '"')
			->will($this->returnValue(5));

		$this->assertTrue(
				$this->fixture->containsHashForRecord($tableName, $uid)
			);
	}

	/**
	 * @test
	 * @covers ::contains
	 */
	public function containsReturnsFalseForEmptyQueryResult() {
		$mockDatabase = $this->getMock(
				'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
				array('exec_SELECTcountRows'), array(), '', FALSE);
		$this->fixture->_set('database', $mockDatabase);
		$tableName = 'foo';
		$hash = 'bar';
		$mockDatabase->expects($this->once())->method('exec_SELECTcountRows')
			->with(
					'hash',
					'tx_duplicatefinder_duplicate_hash',
					'hash="' . $hash . '" AND foreign_table="' . $tableName . '"')
			->will($this->returnValue(0));

		$this->assertFalse(
				$this->fixture->contains($hash, $tableName)
			);
	}

	/**
	 * @test
	 * @covers ::contains
	 */
	public function containsReturnsTrueForNonEmptyQueryResult() {
		$mockDatabase = $this->getMock(
				'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
				array('exec_SELECTcountRows'), array(), '', FALSE);
		$this->fixture->_set('database', $mockDatabase);
		$tableName = 'foo';
		$hash = 'bar';
		$mockDatabase->expects($this->once())->method('exec_SELECTcountRows')
			->with(
					'hash',
					'tx_duplicatefinder_duplicate_hash',
					'hash="' . $hash . '" AND foreign_table="' . $tableName . '"')
			->will($this->returnValue(1));

		$this->assertTrue(
				$this->fixture->contains($hash, $tableName)
			);
	}

	/**
	 * @test
	 * @covers ::getTableNames
	 */
	public function getTableNamesReturnsInitiallyEmptyArray() {
		$mockDatabase = $this->getMock(
				'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
				array('exec_SELECTgetRows'), array(), '', FALSE);
		$this->fixture->_set('database', $mockDatabase);
		$mockDatabase->expects($this->once())->method('exec_SELECTgetRows')
			->with(
					'DISTINCT foreign_table',
					'tx_duplicatefinder_duplicate_hash',
					'foreign_table!=""')
			->will($this->returnValue(FALSE));
		$this->assertSame(
				array(),
				$this->fixture->getTableNames()
				);
	}

	/**
	 * @test
	 * @covers ::getTableNames
	 */
	public function getTableNamesReturnsArrayWithTableNames() {
		$mockDatabase = $this->getMock(
				'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
				array('exec_SELECTgetRows'), array(), '', FALSE);
		$this->fixture->_set('database', $mockDatabase);
		$expectedDatabaseResult = array(
				array('foreign_table' => 'foo'),
				array('foreign_table' => 'bar')
				);
		$expectedResult = array('foo', 'bar');
		$mockDatabase->expects($this->once())->method('exec_SELECTgetRows')
			->with(
					'DISTINCT foreign_table',
					'tx_duplicatefinder_duplicate_hash',
					'foreign_table!=""')
			->will($this->returnValue($expectedDatabaseResult));
		$this->assertSame(
				$expectedResult,
				$this->fixture->getTableNames()
				);
	}

	/**
	 * @test
	 * @covers ::clear
	 */
	public function clearForStringRemovesSingleTable() {
		$fixture = $this->getMock(
			'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
			array('removeByTable'), array(), '', FALSE);
		$tableName = 'foo';
		$fixture->expects($this->once())->method('removeByTable')
			->with($tableName);

		$fixture->clear($tableName);
	}

	/**
	 * @test
	 * @covers ::clear
	 */
	public function clearRemovesAllTablesIfNoneIsGiven() {
		$fixture = $this->getMock(
			'CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository',
			array('removeByTable', 'getTableNames'), array(), '', FALSE);
		$tableNames = array('foo', 'bar');
		$fixture->expects($this->once())->method('getTableNames')
			->will($this->returnValue($tableNames));
		$fixture->expects($this->exactly(2))->method('removeByTable')
			->withConsecutive(
					array('foo'),
					array('bar')
				);

		$fixture->clear();
	}

	/**
	 * @test
	 * @covers ::removeByTable
	 */
	public function removeByTableForStringDeletesHashEntriesAndUpdatesRecord() {
		$mockDatabase = $this->getMock(
				'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
				array('exec_UPDATEquery', 'exec_DELETEquery'), array(), '', FALSE);
		$this->fixture->_set('database', $mockDatabase);
		$tableName = 'foo';
		$expectedUpdateFieldValues = array(
				'is_duplicate' => 0,
				'duplicate_hash_id' => 0
				);
		$mockDatabase->expects($this->once())->method('exec_UPDATEquery')
			->with(
					$tableName,
					'',
					$expectedUpdateFieldValues
					);
		$mockDatabase->expects($this->once())->method('exec_DELETEquery')
			->with(
					'tx_duplicatefinder_duplicate_hash',
					'foreign_table="' . $tableName . '"'
					);

		$this->fixture->removeByTable($tableName);
	}
}
