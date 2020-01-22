<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Elements;

use RuntimeException;
use Imagine\Image\AbstractImage;
use Imagine\Image\AbstractImagine;
use Imagine\Image\Point;

/**
 * Class Image
 *
 * @package Ruzgfpegk\GeneratorsImg\Elements
 */
class Image extends GenericElement
{
	// Element properties
	/**
	 * @var string The image name
	 */
	public $file;
	
	// Processed variables
	/**
	 * @var string The path to the image to render
	 */
	public $imagePath;
	
	
	/**
	 * Image constructor
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
		$this->loadSection($elementParameters);
		$this->postLoad();
	}
	
	
	/**
	 * @param string[] $section Associative array of section parameters
	 *
	 * @throws RuntimeException
	 */
	public function loadSection($section) : void
	{
		parent::loadSection($section);
		
		if (array_key_exists('image', $section)) {
			$this->file = $section['image'];
		} else {
			throw new RuntimeException(
				"File parameter not found for element $this->elementName!\n"
			);
		}
	}
	
	/**
	 * Internal treatments/checks to run after the conf is loaded
	 */
	public function postLoad() : void
	{
		parent::postLoad();
		
		$this->imagePath = $this->getImagePath();
	}
	
	/**
	 * @return null|string The relative image path
	 *
	 * @throws RuntimeException
	 */
	public function getImagePath() : ?string
	{
		$fullPath = null;
		
		if (isset($this->imagePath)) {
			$fullPath = $this->imagePath;
		} else {
			$tmpPath = '../resources/images/' . $this->configName . '/'
				. $this->file;
			if (file_exists($tmpPath)) {
				$fullPath = $tmpPath;
			} else {
				throw new RuntimeException(
					"Image path $tmpPath is invalid!\n"
				);
			}
		}
		
		return $fullPath;
	}
	
	/**
	 * Add an image element on top of a frame
	 * Called from renderImage() in RealTimeGenerator.php
	 *
	 * @param integer $frameNumber Frame on which to add the image
	 */
	public function addToFrame($frameNumber = 0) : void
	{
		$sourceImgObj = $this->globalConfig['imagine']->open($this->imagePath);
		$sourcePosObj = $this->positionObj;
		
		// Crop if needed (TODO: Check if it really works)
		$sourceSizeObj = $sourceImgObj->getSize();
		if ($sourceSizeObj->getWidth()
			> $this->globalConfig['config']->dimensionsObj->getWidth()
			|| $sourceSizeObj->getHeight()
			> $this->globalConfig['config']->dimensionsObj->getHeight()) {
			$sourceZeroPosObj = new Point(0, 0);
			$sourceImgObj     = $sourceImgObj->crop(
				$sourceZeroPosObj,
				$this->globalConfig['config']->dimensionsObj
			);
		}
		
		// Draw the image on the requested frame
		$this->globalConfig['frames'][$frameNumber] =
			$this->globalConfig['frames'][$frameNumber]->paste(
				$sourceImgObj,
				$sourcePosObj
			);
	}
}
