<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Elements;

use RuntimeException;

/**
 * Class Font
 *
 * @package Ruzgfpegk\GeneratorsImg\Elements
 */
class Font extends GenericElement implements ElementInterface
{
	// Element properties
	/**
	 * @var string The font file name
	 */
	public $file;
	
	
	// Processed variables
	/**
	 * @var string The relative font path
	 */
	public $fontPath;
	
	/**
	 * Font constructor
	 *
	 * @param string   $configName         Name of the configuration file of the section
	 * @param string   $elementName        Name of the element
	 * @param string[] $elementParameters  Associative array of section parameters
	 * @param array    $globalConfig       Global elements passed to objects
	 *
	 * @throws RuntimeException
	 */
	public function __construct($configName, $elementName, $elementParameters, $globalConfig)
	{
		parent::__construct($configName, $elementName, $elementParameters, $globalConfig);
		$this->setDefaults();
		$this->loadSection($elementParameters);
		$this->postLoad();
	}
	
	/**
	 * Defaults of the class
	 */
	public function setDefaults() : void
	{
		// TODO: Implement setDefaults() method.
	}
	
	/**
	 * @param string[] $section Associative array of section parameters
	 *
	 * @throws RuntimeException
	 */
	public function loadSection($section) : void
	{
		if (array_key_exists('file', $section)) {
			$this->file = $section['file'];
		} else {
			throw new RuntimeException(
				"File parameter not found for element $this->elementName!\n"
			);
		}
	}
	
	/**
	 * Internal treatments/checks to run after the conf is loaded
	 *
	 * @throws RuntimeException
	 */
	public function postLoad() : void
	{
		$this->fontPath = $this->getFontPath();
	}
	
	
	/**
	 * @return null|string The relative font path
	 *
	 * @throws RuntimeException
	 */
	public function getFontPath() : ?string
	{
		$fullPath = null;
		
		if (isset($this->fontPath)) {
			$fullPath = $this->fontPath;
		} else {
			$tmpPath = '../resources/fonts/' . $this->configName . '/'
				. $this->file;
			if (file_exists($tmpPath)) {
				$fullPath = $tmpPath;
			} else {
				throw new RuntimeException(
					"Font path $tmpPath is invalid!\n"
				);
			}
		}
		
		return $fullPath;
	}
}
