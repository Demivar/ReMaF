<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\ResourceType;


class LoadResourceData extends Fixture {

	private $resources = array(
		'food'          => array('gold'=>0.01),
		'wood'          => array('gold'=>0.02),
		'metal'         => array('gold'=>0.025),
		'goods'         => array('gold'=>0.1),
		'money'         => array('gold'=>0.5),
	);

	public function load(ObjectManager $manager): void {
		foreach ($this->resources as $name=>$data) {
			$type = new ResourceType();
			$type->setName($name);
			$type->setGoldValue($data['gold']);
			$manager->persist($type);
			$this->addReference('resourcetype: '.strtolower($name), $type);
		}
		$manager->flush();
	}
}
