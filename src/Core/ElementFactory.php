<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Core;

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class ElementFactory
 *
 * @package Ruzgfpegk\GeneratorsImg\Core
 */
class ElementFactory
{
	/**
	 * @param string   $elementType       The element to create
	 * @param string   $configName        The configuration name
	 * @param string   $elementName       The element name
	 * @param string[] $elementParameters An array of parameters for the element type
	 * @param array    $globalConfig      Global elements passed to objects
	 *
	 * @return ElementInterface The requested object
	 *
	 * @throws Exception
	 */
	public static function create(
		string $elementType,
		string $configName,
		string $elementName,
		$elementParameters,
		$globalConfig = []
	) : ElementInterface {
		if (empty($elementType)) {
			throw new InvalidArgumentException(
				'The first parameter should be a string containing the element type.<br>'
			);
		}
		if (empty($configName)) {
			throw new InvalidArgumentException(
				'The second parameter should be a string containing the config name.<br>'
			);
		}
		if (empty($elementName)) {
			throw new InvalidArgumentException(
				'The third parameter should be a string containing the element name.<br>'
			);
		}
		if (!is_array($elementParameters)) {
			throw new InvalidArgumentException(
				'The fourth parameter should be an associative array containing '
				. 'the element parameters.<br>'
			);
		}
		
		if (!file_exists( dirname(__DIR__, 1) . '/Elements/' . $elementType . '.php')) {
			throw new RuntimeException("No class found for element $elementType!<br>");
		}
		
		$elementFullType = 'Ruzgfpegk\\GeneratorsImg\\Elements\\'. $elementType;
		
		return new $elementFullType($configName, $elementName, $elementParameters, $globalConfig);
	}
}
