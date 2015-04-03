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
}

