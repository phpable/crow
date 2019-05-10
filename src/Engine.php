<?php
namespace Able\Crow;

use \Able\Crow\Abstractions\AConsumer;
use \Able\Crow\Consumers\CFunction;

use Able\Crow\Utilities\StringBuffer;
use \Able\Helpers\Arr;
use \Able\Helpers\Src;

use \Able\IO\Path;
use \Able\IO\File;
use \Able\IO\Directory;

use \Able\IO\WritingBuffer;
use \Able\Reglib\Regex;

use \Exception;
use \Generator;

class Engine {

	/**
	 * @const string
	 */
	protected const CT_YIELD = 'yield';

	/**
	 * @const string
	 */
	protected const CT_TO_CLASS = 'to_class';

	/**
	 * @const string
	 */
	protected const CT_CLASS = 'class';

	/**
	 * @var callable[]
	 */
	private static array $Resolvers = [];

	/**
	 * @param string $name
	 * @param callable $Handler
	 */
	public final function resolve(string $name, callable $Handler): void {
		self::$Resolvers[$name] = $Handler;
	}

	/**
	 * @var array
	 */
	private array $Queue = [];

	/**
	 * @param File $File
	 * @throws Exception
	 */
	public final function register(File $File): void {
		array_push($this->Queue, $File);
	}

	/**
	 * @var array
	 */
	private array $Cache = [];

	/**
	 * @param string $tag
	 * @param CFunction $Consumer
	 *
	 * @throws Exception
	 */
	protected final function cacheMethod(string $tag, CFunction $Consumer): void {
		if (!Regex::checkVariable($tag)
			&& $Data[2] != '@anonymous') {

				throw new Exception(sprintf('Invalid class name: %s', $tag));
		}

		if (!is_null(Arr::follow($this->Cache,
			'class', $tag = Src::tcm($tag), $Consumer->name()))) {

				throw new Exception(sprintf('Method %s is duplicated for class %s',
					$Consumer->name(), $tag));
		}

		$this->Cache = Arr::place($this->Cache, $Consumer, 'class', $tag, $Consumer->name());
	}

	/**
	 * @param string $name
	 * @return Generator
	 *
	 * @throws Exception
	 */
	protected final function combineMethods(string $name): Generator {
		if (is_null($Signature = Arr::follow($this->Cache, 'class', $name))) {
			throw new Exception(sprintf('Undefined class name: %s!',
				$name));

		}

		foreach ($Signature as $Comsumer) {
			yield from $Comsumer->iterate();
		}
	}

	/**
	 * @param File|null $File
	 * @return Generator
	 *
	 * @throws Exception
	 */
	public function parse(?File $File = null): Generator {
		$Buffer = new StringBuffer();

		/**
		 * This Is the special case and the short function call mostly.
		 * Works pretty good if the only one file is needed to be processed.
		 */
		if (!is_null($File)) {
			$this->register($File);
		}

		foreach ($this->Queue as $File) {
			$Tokens = token_get_all($File->getContent(), TOKEN_PARSE);

			while (count($Tokens) > 0) {
				$Parsed = array_shift($Tokens);

				/**
				 * The injection engine is focused around PHP syntax only,
				 * so none close tags are allowed.
				 */
				if (is_array($Parsed)
					&& $Parsed[0] == T_CLOSE_TAG) {

						throw new Exception('No closing tags are allowed!');
				}

				/**
				 * The queue is gonna be linked into a single file,
				 * so any opening or closing tags have to be ignored.
				 */
				if (is_array($Parsed)
					&& in_array($Parsed[0], [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO])) {

						continue;
				}

				/**
				 * The simple literal are always return as is
				 * without any reformation.
				 */
				if (!is_array($Parsed)) {
					$Buffer->write($Parsed);
					continue;
				}

				/**
				 * The current version of CROW engine is required
				 * the special syntax be wrapped by comments.
				 */
				if ($Parsed[0] == T_COMMENT
					&& preg_match('/\/\*[\s*]*<%([A-Za-z][A-Za-z0-9_]+)(?::(.+))?%>[\s*]*\*\//', $Parsed[1], $Data)) {

					switch (strtolower($Data[1])) {
						case self::CT_YIELD:
							if (!isset(self::$Resolvers[$Data[2]])) {
								throw new Exception(sprintf('Undefined resolver: %s!', $Data[2]));
							}

							$Buffer->consume(self::$Resolvers[$Data[2]]());
							continue 2;

						case self::CT_TO_CLASS:
							$this->cacheMethod($Data[2],
								(new CFunction())->consume($Tokens));

							continue 2;

						case self::CT_CLASS:
							$Buffer->consume($this->combineMethods($Data[2]));
							continue 2;

						default:
						 	throw new Exception(sprintf('Undefine instruction: %s', $Data[0]));
					}
				}

				$Buffer->write($Parsed[1]);
			}
		}

		return $Buffer->iterate();
	}
}
