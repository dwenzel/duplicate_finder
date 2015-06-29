<?php
namespace CPSIT\Test\Unit\Configuration;

use TYPO3\CMS\Core\Tests\UnitTestCase;

class DuplicateRegistryTest extends UnitTestCase {

	/**
	 * Fixture
	 *
	 * @var DuplicateRegistry
	 * @coversDefaultClass \CPSIT\DuplicateFinder\Configuration\DuplicateRegistry
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock(
			'CPSIT\\DuplicateFinder\\Configuration\\DuplicateRegistry',
			array('dummy'), array(), '', FALSE
		);
	}

	/**
	 * @test
	 * @covers ::getExtensionKeys
	 */
	public function getExtensionKeysReturnsInitiallyEmptyArray() {
		$this->assertEquals(
				array(),
				$this->fixture->getExtensionKeys()
				);
	}

	/**
	 * @test
	 * @covers ::getExtensionKeys
	 */
	public function getExtensionKeysReturnsKeys() {
		$extensions = array(
				'fooExtension' => 'barField',
				'barExtension' => 'bazField'
				);
		$expectedResult = array(
				0 => 'fooExtension',
				1 => 'barExtension'
				);
		$this->fixture->_set('extensions', $extensions);

		$this->assertEquals(
				$expectedResult,
				$this->fixture->getExtensionKeys()
				);
	}

	/**
	 * @test
	 * @covers ::getDuplicateAwareTables
	 */
	public function getDuplicateAwareTablesReturnsInitiallyEmptyArray() {
		$this->assertEquals(
				array(),
				$this->fixture->getDuplicateAwareTables()
				);
	}

	/**
	 * @test
	 * @covers ::getDuplicateAwareTables
	 */
	public function getDuplicateAwareTablesReturnsTables() {
		$registry = array(
				'fooTable' => 'barField',
				'barTable' => 'bazField'
				);
		$expectedResult = array(
				0 => 'fooTable',
				1 => 'barTable'
				);
		$this->fixture->_set('registry', $registry);

		$this->assertEquals(
				$expectedResult,
				$this->fixture->getDuplicateAwareTables()
				);
	}

	/**
	 * @test
	 * @covers ::isRegistered
	 */
	public function isRegisteredReturnsInitiallyFalse() {
		$this->assertFalse(
				$this->fixture->isRegistered('fooTable', 'barField')
				);
	}

	/**
	 * Gets a valid extension field
	 *
	 * @param string $extensionKey
	 * @param string $tableName
	 * @param string $fieldName
	 */
	protected function getValidExtensionsField($extensionKey, $tableName, $fieldName) {
		return array(
				$extensionKey => array(
					$tableName => array($fieldName)
					)
				);
	}

	/**
	 * @test
	 * @covers ::isRegistered
	 */
	public function isRegisteredReturnsTrueForRegisteredTable() {
		$registry = array(
				'fooTable' => array(
					'barField' => 'fooValue'
					)
				);
		$this->fixture->_set('registry', $registry);
		
		$this->assertTrue(
				$this->fixture->isRegistered('fooTable', 'barField')
				);
	}

	/**
	 * @test
	 * @covers ::isRegistered
	 */
	public function isRegisteredReturnsTrueForRegisteredTableWithDefaultFieldName() {
		$registry = array(
				'fooTable' => array(
					'is_duplicate' => 'fooValue'
					)
				);
		$this->fixture->_set('registry', $registry);
		
		$this->assertTrue(
				$this->fixture->isRegistered('fooTable')
				);
	}

	/**
	 * @test
	 * @covers ::isRegistered
	 */
	public function isRegisteredReturnsFalseForRegisteredTableWithWrongFieldName() {
		$registry = array(
				'fooTable' => array(
					'is_duplicate' => 'fooValue'
					)
				);
		$this->fixture->_set('registry', $registry);
		
		$this->assertFalse(
				$this->fixture->isRegistered('fooTable', 'barField')
				);
	}

	/**
	 * @test
	 * @covers ::getDatabaseTableDefinitions
	 */
	public function getDatabaseTableDefinitionsReturnsInitiallyEmptyString() {
		$this->assertSame(
				'',
				$this->fixture->getDatabaseTableDefinitions()
				);
	}

	/**
	 * Gets the Sql template
	 */
	protected function getSqlTemplate() {
		return str_repeat(PHP_EOL, 3) . 'CREATE TABLE %s (' . PHP_EOL . '  %s tinyint(3) DEFAULT \'0\' NOT NULL,' . PHP_EOL  . ' duplicate_hash_id int(11) DEFAULT \'0\' NOT NULL' . PHP_EOL . ');' . str_repeat(PHP_EOL, 3);
	}

	/**
	 * @test
	 * @covers ::__construct
	 */
	public function constructorSetsSqlTemplate() {
		$this->fixture->_call('__construct');
		$this->assertSame(
				$this->getSqlTemplate(),
				$this->fixture->_get('sqlTemplate')
				);
	}

	/**
	 * @test
	 * @covers ::getDatabaseTableDefinition
	 */
	public function getDatabaseTableDefinitionReturnsInitiallyEmptyString() {
		$this->assertSame(
				'',
				$this->fixture->getDatabaseTableDefinition('fooTable')
				);
	}

	/**
	 * @test
	 * @covers ::getDatabaseTableDefinition
	 */
	public function getDatabaseTableDefinitionReturnsSqlForRegisteredTableAndFields() {
		$extensions = $this->getValidExtensionsField('fooExtension', 'fooTable', 'barField');
		$this->fixture->_set('extensions', $extensions);
		$this->fixture->_set('sqlTemplate', $this->getSqlTemplate());
		$this->assertSame(
				sprintf($this->getSqlTemplate(), 'fooTable', 'barField'),
				$this->fixture->getDatabaseTableDefinition('fooExtension')
				);
	}

	/**
	 * @test
	 * @covers ::add
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1427275047
	 * @expectedExceptionMessage No or invalid table name "" given.
	 * @expectedExceptionCode 1427275047
	 * @expectedExceptionMessage No or invalid extension key "" given.
	 */
	public function addThrowsExceptionForMissingArguments() {
		$this->fixture->add('', '');
	}

	/**
	 * @test
	 * @covers ::add
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1427275047
	 * @expectedExceptionMessage No or invalid table name "6" given.
	 * @expectedExceptionCode 1427275072
	 * @expectedExceptionMessage No or invalid extension key "5" given.
	 */
	public function addThrowsExceptionForInvalidArguments() {
		$this->fixture->add(5, 6);
	}

	/**
	 * @test
	 * @covers ::add
	 * @expectedException \RuntimeException
	 * @expectedExceptionCode 1427289509
	 * @expectedExceptionMessage Dynamic config file for table fooTable not found
	 */
	public function addThrowsExceptionForMissingConfigFile() {
		$tableName = 'fooTable';
		$GLOBALS['TCA'][$tableName]['ctrl']['dynamicConfigFile'] = 'barFile';

		$this->fixture->add('fooExtension', $tableName, 'fooField');
	}

	/**
	 * @test
	 * @covers ::add
	 */
	public function addReturnsFalseForRegisteredTableAndField() {
		$fixture = $this->getMock(
				'CPSIT\\DuplicateFinder\\Configuration\\DuplicateRegistry',
				array('isRegistered'), array(), '', FALSE);
		$tableName = 'bar';
		$fieldName = 'foo';

		$fixture->expects($this->once())->method('isRegistered')
			->with($tableName, $fieldName)
			->will($this->returnValue(TRUE));
		$this->assertFalse(
				$fixture->add('fooExtension', $tableName, $fieldName)
				);
	}

	/**
	 * @test
	 * @covers ::add
	 */
	public function addReturnsFalseForUnconfiguredTable() {
		$tableName = 'bar';
		$fieldName = 'foo';

		$this->assertFalse(
				$this->fixture->add('fooExtension', $tableName, $fieldName)
				);
	}

	/**
	 * @test
	 * @covers ::add
	 */
	public function addAddsConfiguredTable() {
		$tableName = 'bar';
		$fieldName = 'foo';
		$GLOBALS['TCA'][$tableName]['columns'] = array($fieldName);

		$this->assertTrue(
				$this->fixture->add('fooExtension', $tableName, $fieldName)
				);
		$this->assertTrue(
				$this->fixture->isRegistered($tableName, $fieldName)
				);
	}
}

