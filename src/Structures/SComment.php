<?php
namespace Able\Crow\Structures;

use \Able\Struct\AStruct;

class SComment extends AStruct {

	/**
	 * @var array
	 */
	protected static array $Prototype = ['multiline'];

	/**
	 * @param bool $value
	 * @return bool
	 * @throws \Exception
	 */
	protected final function setMultilineProperty(bool $value): bool {
		return $value;
	}
}
