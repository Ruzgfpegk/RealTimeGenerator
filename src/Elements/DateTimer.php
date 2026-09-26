<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Elements;

use Exception;

use Ruzgfpegk\GeneratorsImg\Core\AnimationTrait;

/**
 * Class DateTimer
 *
 * @package Ruzgfpegk\GeneratorsImg\Elements
 */
class DateTimer extends Date
{
	use AnimationTrait;
	
	// Element properties
	
	/**
	 * DateTimer constructor
	 *
	 * @param string   $configName         Name of the configuration file of the section
	 * @param string   $elementName        Name of the element
	 * @param string[] $elementParameters  Associative array of section parameters
	 * @param array    $globalConfig       Global elements passed to objects
	 *
	 * @throws Exception
	 */
	public function __construct(string $configName, string $elementName, array $elementParameters, array $globalConfig)
	{
		parent::__construct($configName, $elementName, $elementParameters, $globalConfig);
		$this->setDefaults();
		$this->loadSection($elementParameters);
		$this->postLoad();
		$this->strToDate($this->globalConfig['config']->frames, $this->globalConfig['config']->interval);
	}
}
