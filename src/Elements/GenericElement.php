<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Elements;

use Imagine\Image\Point;

use Ruzgfpegk\GeneratorsImg\Core\ElementInterface;

/**
 * Class GenericElement
 *
 * @package Ruzgfpegk\GeneratorsImg\Elements
 */
abstract class GenericElement implements ElementInterface
{
	// Common element properties
	/**
	 * Position of the element, as a string 'x,y'
	 */
	public string $position;
	
	/**
	 * Opacity of the element, between 0 (transparent) and 100 (opaque).
	 */
	public int $opacity;
	
	/**
	 * A comma-separated list of frames on which the element is to be drawn, an empty list meaning "all the frames".
	 * @todo Implement
	 */
	public string $onFrames;
	
	
	// Objects and processes values
	/**
	 * Object version of the position property
	 */
	public Point $positionObj;
	
	/**
	 * @var int[] Array version of the onFrames property
	 * @todo Implement, See postLoad()
	 */
	public array $onFramesArr;
	
	
	/**
	 * GenericElement constructor.
	 *
	 * @param string   $configName         The name of the configuration containing the element (also in the configuration file filename).
	 * @param string   $elementName        The identifier of the element (section of the configuration file)
	 * @param string[] $elementParameters  Associative array of section parameters
	 * @param array    $globalConfig       Global objects for the whole image
	 */
	public function __construct(public string $configName, public string $elementName, array $elementParameters, public array $globalConfig)
	{
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
	public function loadSection(array $section) : void
	{
		if (array_key_exists('position', $section)) {
			$this->position = $section['position'];
		}
		
		if (array_key_exists('opacity', $section)) {
			$this->opacity = (int) $section['opacity'];
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
