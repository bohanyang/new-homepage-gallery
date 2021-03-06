<?php

namespace App\Model;

use Spatie\DataTransferObject\DataTransferObject;

final class ImageView extends DataTransferObject
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

    /** @var bool */
    public $uhd;

    /** @var \App\Model\Record[] */
    public $records;
}
