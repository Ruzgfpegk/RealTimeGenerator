<?php

namespace Ruzgfpegk\GeneratorsImg;

use Imagine\Image\Point;

/**
 * Class RTG_Element
 *
 * Each section of the selected configuration file becomes an "element".
 *
 * @package Ruzgfpegk\GeneratorsImg
 */
class RealTimeGeneratorElement
{
	/**
	 * @var string The name of the parent configuration (also in the configuration file filename)
	 */
	public $configName;
	/**
	 * @var string The identifier of the element (section of the configuration file)
	 */
	public $elementName;
	/**
	 * @var string[] An associative array of the parameters inside the element configuration
	 */
	public $properties;
	
	
	/**
	 * RTG_Element constructor.
	 *
	 * @param string   $configName Configuration (folder) of the element
	 * @param string   $section    Element name
	 * @param string[] $parameters Element parameters (associative array)
	 */
	public function __construct($configName, $section, $parameters)
	{
		if (!is_string($configName) || empty($configName)) {
			throw new \InvalidArgumentException(
				"The first parameter should be a string containing the config name.\n"
			);
		}
		if (!is_string($section) || empty($section)) {
			throw new \InvalidArgumentException(
				"The second parameter should be a string containing the element name.\n"
			);
		}
		if (!is_array($parameters) || !array_key_exists('type', $parameters)) {
			throw new \InvalidArgumentException(
				'The third parameter should be an associative array containing ',
				"the element parameters, with at least a 'type' key.\n"
			);
		}
		
		$this->configName  = $configName;
		$this->elementName = $section;
		
		// Every element should have a type.
		// The element defaults are set here for each case
		switch ($parameters['type']) {
			case 'image':
				if (!array_key_exists('position', $parameters)) {
					$parameters['position'] = '0,0';
				}
				$this->properties = $parameters;
				break;
			case 'countdown':
			case 'timer':
			case 'date':
			case 'text':
				if (!array_key_exists('string', $parameters)) {
					$parameters['string'] = 'UNDEFINED STRING';
				}
				if (!array_key_exists('size', $parameters)) {
					$parameters['size'] = 12;
				}
				if (!array_key_exists('color', $parameters)) {
					$parameters['color'] = '255,255,255';
				}
				if (!array_key_exists('opacity', $parameters)) {
					$parameters['opacity'] = 100;
				}
				$this->properties = $parameters;
				break;
			case 'font':
			case 'color':
				$this->properties = $parameters;
				break;
			default:
		}
		
		//TODO: Remove spaces in values without quotes
		
		// Some factorisation for common parameters
		if (array_key_exists('color', $this->properties)) {
			$this->properties['color'] = explode(
				',',
				$this->properties['color']
			);
		}
		if (array_key_exists('position', $this->properties)) {
			$this->properties['position'] = new Point(
				...explode(
					',',
					$this->properties['position']
				)
			);
		}
	}
}
