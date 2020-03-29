<?php

namespace App\Design;

use Spatie\DataTransferObject\DataTransferObject;

class Image extends DataTransferObject
{
	/** @var string $name */
	public $name;

	/** @var string $urlbase */
	public $urlbase;

	/** @var string $copyright */
	public $copyright;

	/** @var bool $wp */
	public $wp;

	/** @var ?array $vid */
	public $vid;
}
