<?php

namespace App\Twig;

use App\Entity\ActivityReport;
use App\Entity\Artifact;
use App\Entity\Association;
use App\Entity\BattleReport;
use App\Entity\BuildingType;
use App\Entity\Character;
use App\Entity\Deity;
use App\Entity\DungeonCardType;
use App\Entity\EquipmentType;
use App\Entity\Event;
use App\Entity\EventLog;
use App\Entity\FeatureType;
use App\Entity\House;
use App\Entity\Law;
use App\Entity\Place;
use App\Entity\Realm;
use App\Entity\RealmPosition;
use App\Entity\Settlement;
use App\Entity\SoldierLog;
use App\Entity\Unit;
use App\Entity\War;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class MessageTranslateExtension extends AbstractExtension {

	private $absolute=false;

	// FIXME: type hinting for $translator removed because the addition of LoggingTranslator is breaking it
	public function __construct(private EntityManagerInterface $em, private TranslatorInterface $trans, private LinksExtension $links, private GeographyExtension $geo) {
	}

	public function getFilters(): array {
		return array(
			new TwigFilter('messagetranslate', array($this, 'messageTranslate'), array('is_safe' => array('html'))),
			new TwigFilter('eventtranslate', array($this, 'eventTranslate'), array('is_safe' => array('html'))),
			new TwigFilter('logtranslate', array($this, 'logTranslate'), array('is_safe' => array('html'))),
		);
	}

	// TODO: different strings if owner views his own log (you have... instead of has, etc.)
	public function eventTranslate(Event $event, $absolute=false): string {
		$this->absolute = $absolute;
		$data = $this->parseData($event->getData());
		if ($event->getContent()=='multi') {
			$strings = array();
			foreach ($data['events'] as $subevent) {
				$subdata = $data;
				unset($subdata['events']);
				$strings[] = $this->trans->trans($subevent, $subdata, "communication");
			}
			return implode("<br />", $strings);
		} else {
			if (array_key_exists('%subtrans%', $data)) {
				if (array_key_exists('%transprefix%', $data)) {
					if (array_key_exists('%transsuffix%', $data)) {
						$data['%title%'] = $this->trans->trans($data['%transprefix%'].$data['%title%'].$data['%transsuffix%'], [], $data['%subtrans%']);
					} else {
						$data['%title%'] = $this->trans->trans($data['%transprefix%'].$data['%title%'], [], $data['%subtrans%']);
					}
				} else {
					if (array_key_exists('%transsuffix%', $data)) {
						$data['%title%'] = $this->trans->trans($data['%title%'].$data['%transsuffix%'], [], $data['%subtrans%']);
					} else {
						$data['%title%'] = $this->trans->trans($data['%title%'], [], $data['%subtrans%']);
					}
				}

			}
			return $this->trans->trans($event->getContent(), $data, "communication");
		}
	}

	public function logTranslate(SoldierLog $event): string {
		$data = $this->parseData($event->getData());
		if ($event->getContent()=='multi') {
			$strings = array();
			foreach ($data['events'] as $subevent) {
				$subdata = $data;
				unset($subdata['events']);
				$strings[] = $this->trans->trans($subevent, $subdata, "communication");
			}
			return implode("<br />", $strings);
		} else {
			return $this->trans->trans($event->getContent(), $data, "communication");
		}
	}

	public function messageTranslate($input): string {
		if (is_array($input) || is_object($input)) {
			$strings = array();
			foreach ($input as $in) {
				$strings[] = $this->messageTranslateOne($in);
			}
			return implode("<br />", $strings);
		} else {
			return $this->messageTranslateOne($input);
		}
	}

	public function messageTranslateOne($input): string {
		$json = json_decode($input);
		if (!$json) return $input;

		if (!isset($json->text)) return $input;
		if (isset($json->data)&& (is_array($json->data)||is_object($json->data))) {
			$data=$this->parseData($json->data);
		} else {
			$data=array();
		}
		return $this->trans->trans($json->text, $data, "communication");
	}

	public function getName(): string {
		return 'message_translate_extension';
	}


	private function parseData($input): array {
		if (!$input) return array();
		$data=array();
		$domain = $input['domain'] ?? 'communication';
		foreach ($input as $key=>$value) {
			if (preg_match('/%link-([^-]+)(-.*)?%/', $key, $matches)) {
				// link elements, syntax %link-(type)%
				$subkey = $matches[1];
				if (isset($matches[2])) {
					$index = str_replace('-', '_', $matches[2]);
				} else {
					$index = '';
				}
				switch ($subkey) {
					case 'war':
						$war = $this->em->getRepository(War::class)->find($value);
						$data['{war'.$index.'}'] = $this->links->ObjectLink($war, false, $this->absolute);
						break;
					case 'artifact':
						$artifact = $this->em->getRepository(Artifact::class)->find($value);
						$data['{artifact'.$index.'}'] = $this->links->ObjectLink($artifact, false, $this->absolute);
						break;
					case 'place':
						$place = $this->em->getRepository(Place::class)->find($value);
						$data['{place'.$index.'}'] = $this->links->ObjectLink($place, false, $this->absolute);
						break;
					case 'battle':
						$battle = $this->em->getRepository(BattleReport::class)->find($value);
						$data['{battle'.$index.'}'] = $this->links->ObjectLink($battle, false, $this->absolute);
						break;
					case 'activityreport':
						$report = $this->em->getRepository(ActivityReport::class)->find($value);
						$data['{activityreport'.$index.'}'] = $this->links->ObjectLink($report, false, $this->absolute);
						break;
					case 'log':
						$log = $this->em->getRepository(EventLog::class)->find($value);
						$data['{log'.$index.'}'] = $this->links->ObjectLink($log, false, $this->absolute);
						break;
					case 'realm':
						$realm = $this->em->getRepository(Realm::class)->find($value);
						$data['{realm'.$index.'}'] = $this->links->ObjectLink($realm, false, $this->absolute);
						break;
					case 'realmposition':
						$position = $this->em->getRepository(RealmPosition::class)->find($value);
						$data['{realmposition'.$index.'}'] = $this->links->ObjectLink($position, false, $this->absolute);
						break;
					case 'settlement':
						$settlement = $this->em->getRepository(Settlement::class)->find($value);
						$data['{settlement'.$index.'}'] = $this->links->ObjectLink($settlement, false, $this->absolute);
						break;
					case 'character':
						$character = $this->em->getRepository(Character::class)->find($value);
						$data['{character'.$index.'}'] = $this->links->ObjectLink($character, false, $this->absolute);
						break;
					case 'buildingtype':
						$type = $this->em->getRepository(BuildingType::class)->find($value);
						$data['{buildingtype'.$index.'}'] = $this->links->ObjectLink($type, false, $this->absolute);
						break;
					case 'featuretype':
						$type = $this->em->getRepository(FeatureType::class)->find($value);
						$data['{featuretype'.$index.'}'] = $this->links->ObjectLink($type, false, $this->absolute);
						break;
					case 'item':
						// this can be 0
						if ($value==0) {
							$data['%item'.$index.'}'] = '-';
						} else {
							$type = $this->em->getRepository(EquipmentType::class)->find($value);
							$data['%item'.$index.'}'] = $this->links->ObjectLink($type, false, $this->absolute);
						}
						break;
					case 'house':
						$house = $this->em->getRepository(House::class)->find($value);
						$data['{house'.$index.'}'] = $this->links->ObjectLink($house, false, $this->absolute);
						break;
					case 'unit':
						$unit = $this->em->getRepository(Unit::class)->find($value);
						$data['{unit'.$index.'}'] = $this->links->ObjectLink($unit, false, $this->absolute);
						break;
					case 'assoc':
						$assoc = $this->em->getRepository(Association::class)->find($value);
						$data['{assoc'.$index.'}'] = $this->links->ObjectLink($assoc, false, $this->absolute);
						break;
					case 'deity':
						$deity = $this->em->getRepository(Deity::class)->find($value);
						$data['{deity'.$index.'}'] = $this->links->ObjectLink($deity, false, $this->absolute);
						break;
					case 'law':
						$law = $this->em->getRepository(Law::class)->find($value);
						$data['{law'.$index.'}'] = $this->links->ObjectLink($law, false, $this->absolute);
						break;
					default:
						if (is_array($value)) {
							$data[$key]=$this->trans->trans($value['key'], $value);
						} else {
							$data[$key]=$value;
						}
				}
			} elseif (preg_match('/%name-([^-]+)(-.*)?%/', $key, $matches)) {
				// translation elements, syntax %name-(type)%
				$subkey = $matches[1];
				$index = $matches[2] ?? '';
				switch ($subkey) {
					case 'card':
						$card = $this->em->getRepository(DungeonCardType::class)->find($value);
						$data['%card'.$index.'%'] = "<em>".$this->trans->trans('card.'.$card->getName().'.title', array(), $domain)."</em>";
						break;
					case 'distance':
						$data['%distance'.$index.'%'] = $this->geo->distanceFilter($value);
						break;
					case 'direction':
						$data['%direction'.$index.'%'] = $this->trans->trans($this->geo->directionFilter($value, true));
						break;
				}
			} else {
				$data[$key]=$value;
			}
		}
		return $data;
	}

}
