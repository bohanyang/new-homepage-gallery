<?php

namespace App\Design;

use App\NormalizedDate;
use Spatie\DataTransferObject\DataTransferObject;

class Record extends DataTransferObject
{
	/** @var string $market */
	public $market;

	/** @var NormalizedDate $date */
	public $date;

	/** @var string $description */
	public $description;

	/** @var ?string $link */
	public $link;

	/** @var ?array $hotspots */
	public $hotspots;

	/** @var ?array $messages */
	public $messages;

	/** @var ?array $coverstory */
	public $coverstory;

	/** @var Image $image */
	public $image;
}
