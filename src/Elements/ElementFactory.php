<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Elements;

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class ElementFactory
 *
 * @package Ruzgfpegk\GeneratorsImg\Elements
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
				"The first parameter should be a string containing the element type.\n"
			);
		}
		if (empty($configName)) {
			throw new InvalidArgumentException(
				"The second parameter should be a string containing the config name.\n"
			);
		}
		if (empty($elementName)) {
			throw new InvalidArgumentException(
				"The third parameter should be a string containing the element name.\n"
			);
		}
		if (!is_array($elementParameters)) {
			throw new InvalidArgumentException(
				'The fourth parameter should be an associative array containing '
				. "the element parameters.\n"
			);
		}
		
		if (!file_exists(__DIR__ . '/' . $elementType . '.php')) {
			throw new RuntimeException("No class found for element $elementType!\n");
		}
		
		$elementFullType = 'Ruzgfpegk\\GeneratorsImg\\Elements\\'. $elementType;
		
		return new $elementFullType($configName, $elementName, $elementParameters, $globalConfig);
	}
}
