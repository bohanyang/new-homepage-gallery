<?php

namespace App\Repository\LeanCloud;

use App\Model\Date;
use App\Repository\ImagePointerInterface;
use LeanCloud\LeanObject;

final class Image extends LeanObject implements ImagePointerInterface
{
    use LeanObjectTrait;

    public const CLASS_NAME = 'Image';

    protected static $className = self::CLASS_NAME;

    public function toModelParams() : array
    {
        $data = $this->getData();
        unset($data['available'], $data['lastAppearedOn'], $data['firstAppearedOn']);
        $data['uhd'] = $this->hasUhd();

        return $data;
    }

    public function getWp() : bool
    {
        return $this->get('wp');
    }

    public function getCopyright() : string
    {
        return $this->get('copyright');
    }

    public function getLastAppearedOn() : Date
    {
        return Date::createFromUTC($this->get('lastAppearedOn'));
    }

    public function getFirstAppearedOn() : Date
    {
        return Date::createFromUTC($this->get('firstAppearedOn'));
    }

    public function setLastAppearedOn(Date $date) : void
    {
        $this->set('lastAppearedOn', $date->get());
    }

    public function setFirstAppearedOn(Date $date) : void
    {
        $this->set('firstAppearedOn', $date->get());
    }

    private const UHD_EXCEPTION = [
        "ChipmunkCheeks",
        "BauhausArchive",
        "MiracleGarden",
        "HopeValley",
        "RielBridge",
        "TwrMawr",
        "Paepalanthus",
        "CoveSpires",
        "Mokuren",
        "KiteFestivalChatelaillon",
        "AutumnTreesNewEngland",
        "HidingEggs",
        "LaysanAlbatross",
        "EasterFountain",
        "CasaBatllo",
        "StGeorgePainting",
        "RainforestMoss",
        "MaharashtraParagliding",
        "Flatterulme",
        "AustralianNationalWarMemorial",
        "DowntownToronto",
        "GlenfinnanViaduct",
        "may1",
        "GoliSoda",
        "RamadanCharminar",
        "SaintKabir",
        "SalcombeDevon",
        "GujaratStepWell",
        "KagamiMirror",
        "Mouse2020",
        "AlpsWinter"
    ];

    private function hasUhd() : bool
    {
        return $this->get('firstAppearedOn') >= Date::createFromYmd('20190415')->get()
            && !in_array($this->get('name'), self::UHD_EXCEPTION, true);
    }
}
