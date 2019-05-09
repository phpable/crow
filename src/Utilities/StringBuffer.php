<?php
namespace Able\Crow\Utilities;

use \Able\Helpers\Str;
use \Able\Prototypes\IIteratable;

use \Generator;

class StringBuffer
	implements IIteratable {

	/**
	 * @var array
	 */
	private array $Data = [];

	/**
	 * @param $value
	 */
	public final function write(string $value): void {
		array_push($this->Data, $value);
	}

	/**
	 * @param Generator $value
	 */
	public final function consume(Generator $value): void  {
		array_push($this->Data, $value);
	}

	/**
	 * @return Generator
	 */
	public final function iterate(): Generator {
		foreach ($this->Data as $_) {
			if ($_ instanceof Generator) {
				yield from $_;
			} else {
				yield Str::cast($_);
			}
		}
	}
}
