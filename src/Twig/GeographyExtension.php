<?php

namespace App\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class GeographyExtension extends AbstractExtension {

	protected $league = 7532.5;
	protected $mile = 1852;
	protected $yard = 0.9144;
	protected $hectare = 10000;
	protected $sqm = 3429904; // square mile = $mile^2 -- ugly: this is also hard-coded in Geography.php
	protected $sql = 56738556.25; // square league = $league^2

	protected $trans;

	// FIXME: type hinting removed because the addition of LoggingTranslator is breaking it
	public function __construct(TranslatorInterface $trans) {
		$this->trans = $trans;
	}


	public function getFilters() {
		return array(
			new TwigFilter('direction', array($this, 'directionFilter')),
			new TwigFilter('distance', array($this, 'distanceFilter'), array('is_safe' => array('html'))),
			new TwigFilter('area', array($this, 'areaFilter'), array('is_safe' => array('html'))),
		);
	}

	public function directionFilter($rad, $long=false, $rough=false) {
		if ($long) $format='long'; else $format='short';
		$deg = $rad/(2*pi())*360;

		if ($rough) {
			if ($deg<45) return "direction.$format.north";
			if ($deg<135) return "direction.$format.east";
			if ($deg<225) return "direction.$format.south";
			if ($deg<315) return "direction.$format.west";
			return "direction.$format.north";
		} else {
			if ($deg<22.5) return "direction.$format.north";
			if ($deg<67.5) return "direction.$format.northeast";
			if ($deg<112.5) return "direction.$format.east";
			if ($deg<157.5) return "direction.$format.southeast";
			if ($deg<202.5) return "direction.$format.south";
			if ($deg<247.5) return "direction.$format.southwest";
			if ($deg<292.5) return "direction.$format.west";
			if ($deg<337.5) return "direction.$format.northwest";
			return "direction.$format.north";
		}
	}

	public function distanceFilter($distance, $abbrev=false) {
		$yards = round($distance / $this->yard);

		if ($distance < $this->mile * 1.5) {
			if ($abbrev) {
				$unit = 'y';
			} else {
				$unit = 'yard';
			}
			return $this->trans->trans("dist.".$unit,array('count'=>$yards, '%value%' => $yards));
		}
		$tt = $this->trans->trans("dist.yard", array('count'=>$yards, '%value%' => $yards));
		$miles = $value = round(($distance/$this->mile)*10)/10;

		if ($distance < $this->league * 2.5) {
			$value = $miles;
			if ($abbrev) {
				$unit = 'mi';
			} else {
				$unit = 'mile';
			}
		} else {
			$tt.=" / ".$this->trans->trans("dist.mile", array('count'=> $miles, '%value%' => $miles));
			if ($distance < $this->league * 10) {
				$value = round(($distance/$this->league)*10)/10;
				$unit = 'league';
			} else {
				$value = round($distance/$this->league);
				$unit = 'league';
			}
		}

		$result = $this->trans->trans("dist.".$unit, array('count'=> $value, '%value%' => $value));

		return '<span class="tt" title="'.$tt.'">'.$result.'</span>';
	}

	public function areaFilter($area) {
		$hectares = round($area / $this->hectare);
		$tt = $this->trans->trans("area.hectare", array('count' => $hectares, '%value%' => $hectares));

		if ($area < $this->sqm * 1.5) {
			return $tt;
		}
		$sqm = round(($area/$this->sqm)*10)/10;

		if ($area < $this->sql * 2.5) {
			$value = $sqm;
			$unit = 'sqm';
		} else {
			$tt.=" / ".$this->trans->trans("area.sqm", array('count'=>$sqm, '%value%' => $sqm));
			if ($area < $this->sql * 10) {
				$value = round(($area/$this->sql)*10)/10;
				$unit = 'sql';
			} else {
				$value = round($area/$this->sql);
				$unit = 'sql';
			}
		}

		$result = $this->trans->trans("area.".$unit, array('count'=>$value, '%value%' => $value));
		return '<span class="tt" title="'.$tt.'">'.$result.'</span>';
	}

	public function getName() {
		return 'geography_extension';
	}
}
