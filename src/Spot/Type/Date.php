<?php
namespace Spot\Type;

use Spot\Entity;

class Date extends Datetime
{
	/** @var string */
	protected $format = 'Y-m-d';
}
