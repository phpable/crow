<?php
namespace Able\Crow\Abstractions;

use \Able\Prototypes\IIteratable;
use \Able\Prototypes\ICountable;

use \Generator;

abstract class AConsumer
	implements IIteratable, ICountable {

	/**
	 * @var string[]
	 */
	private array $Cache = [];

	/**
	 * @return int
	 */
	public final function count(): int {
		return count($this->Cache);
	}

	/**
	 * @param string $value
	 */
	protected final function cache(string $value): void {
		array_push($this->Cache, $value);
	}

	/**
	 * @return Generator
	 */
	public final function iterate(): Generator {
		yield from $this->Cache;
	}

	/**
	 * @param array $Tokens
	 * @return AConsumer
	 */
	abstract function consume(array &$Tokens): AConsumer;
}
