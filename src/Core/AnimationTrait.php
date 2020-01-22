<?php
declare( strict_types = 1 );

namespace Ruzgfpegk\GeneratorsImg\Core;

/**
 * Trait AnimationTrait
 *
 * @package Ruzgfpegk\GeneratorsImg\Traits
 *
 * @todo implement that nicely if possible
 */
trait AnimationTrait
{
	/**
	 * @var integer The number of seconds between two updates of the element.
	 *              It should be a multiple of the main "interval" property.
	 *
	 * @todo Allow elements to have their own interval.
	 */
	public $interval;
	
	/**
	 * @var integer The number of frames for the element animation
	 */
	public $numberOfFrames;
}
