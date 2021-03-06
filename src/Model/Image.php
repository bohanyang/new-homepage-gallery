<?php

namespace App\Model;

use Spatie\DataTransferObject\DataTransferObject;

final class Image extends DataTransferObject
{
	/** @var string */
	public $name;

	/** @var string */
	public $urlbase;

	/** @var string */
	public $copyright;

	/** @var bool */
	public $wp;

	/** @var array|null */
	public $vid;

	/** @var bool|null */
	public $uhd;
}
