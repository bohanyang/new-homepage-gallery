<?php

namespace App\Model;

use Spatie\DataTransferObject\DataTransferObject;

final class Record extends DataTransferObject
{
	/** @var string */
	public $market;

	/** @var \App\Model\Date */
	public $date;

	/** @var string */
	public $description;

	/** @var string|null */
	public $link;

	/** @var array|null */
	public $hotspots;

	/** @var array|null */
	public $messages;

	/** @var array|null */
	public $coverstory;
}
