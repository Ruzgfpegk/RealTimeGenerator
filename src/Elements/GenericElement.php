<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Elements;

use Imagine\Image\Point;

/**
 * Class GenericElement
 *
 * @package Ruzgfpegk\GeneratorsImg\Elements
 */
abstract class GenericElement
{
	/**
	 * @var array Global objects for the whole image
	 */
	public $globalConfig;
	
	// Meta-properties
	/**
	 * @var string The name of the configuration containing the element
	 *             (also in the configuration file filename).
	 */
	public $configName;
	
	/**
	 * @var string The identifier of the element
	 *             (section of the configuration file)
	 */
	public $elementName;
	
	
	// Common element properties
	/**
	 * @var string Position of the element, as a string 'x,y'
	 */
	public $position;
	
	/**
	 * @var integer Opacity of the element, between 0 (transparent) and 100 (opaque).
	 */
	public $opacity;
	
	/**
	 * @var string A list of frames on which the element is to be drawn,
	 *             empty list meaning "all the frames".
	 */
	public $onFrames;
	
	
	// Objects and processes values
	/**
	 * @var Point Object version of the position property
	 */
	public $positionObj;
	/**
	 * @var int[] Array version of the onFrames property
	 */
	public $onFramesArr;
	
	
	/**
	 * GenericElement constructor.
	 *
	 * @param string   $configName         Name of the configuration file of the section
	 * @param string   $elementName        Name of the element
	 * @param string[] $elementParameters  Associative array of section parameters
	 * @param array    $globalConfig       Global elements passed to objects
	 */
	public function __construct($configName, $elementName, $elementParameters, $globalConfig)
	{
		$this->configName   = $configName;
		$this->elementName  = $elementName;
		$this->globalConfig = $globalConfig;
		$this->setDefaults();
		$this->loadSection($elementParameters);
		$this->postLoad();
	}
	
	/**
	 * Defaults of the class
	 */
	public function setDefaults() : void
	{
		$this->position = '0,0';
		$this->opacity  = 100;
		$this->onFrames = '';
	}
	
	/**
	 * Load element properties from the configuration file
	 *
	 * @param string[] $section Associative array of section parameters
	 */
	public function loadSection($section) : void
	{
		if (array_key_exists('position', $section)) {
			$this->position = $section['position'];
		}
		
		if (array_key_exists('opacity', $section)) {
			$this->opacity = $section['opacity'];
		}
		
		if (array_key_exists('onFrames', $section)) {
			$this->onFrames = $section['onFrames'];
		}
	}
	
	/**
	 * Internal treatments/checks to run after the conf is loaded
	 */
	public function postLoad() : void
	{
		// Prepare properties for further use
		$this->positionObj = new Point(
			...explode(
				',',
				$this->position
			)
		);
		/* TODO
		$this->onFramesArr = explode(
			',',
			$this->onFrames
		);*/
	}
}
