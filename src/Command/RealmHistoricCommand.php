<?php

namespace App\Command;

use App\Entity\Realm;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class RealmHistoricCommand extends Command {

	private EntityManagerInterface $em;
	private int $cycle = 0;
	private string $base_cmd = "/usr/bin/convert ~/realm-movies/base.png";
	private string $output_path = "/home/maf/realm-movies/";
	private string $mvg = "";
	private ArrayCollection $all;

	public function __construct(EntityManagerInterface $em) {
		$this->em = $em;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('maf:realm:historic')
			->setDescription('Get the realm and subrealm IDs at a specific cycle')
			->addArgument('realm', InputArgument::REQUIRED, 'realm name or id')
			->addArgument('start', InputArgument::REQUIRED, 'start cycle')
			->addArgument('end', InputArgument::REQUIRED, 'end cycle')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): void {
		$r = $input->getArgument('realm');
		$start_cycle = $input->getArgument('start');
		$end_cycle = $input->getArgument('end');

		if (intval($r)) {
			$realm = $this->em->getRepository(Realm::class)->find(intval($r));
		} else {
			$realm = $this->em->getRepository(Realm::class)->findOneBy(['name'=>$r]);
		}

		$query = $this->em->createQuery('SELECT min(s.cycle) as minimum, max(s.cycle) as maximum FROM App\Entity\StatisticSettlement s JOIN s.realm r WHERE r = :me');
		$query->setParameter('me', $realm);
		$result = $query->getOneOrNullResult();

		if ($start_cycle < $result['minimum']) {
			$start_cycle = $result['minimum'];
		}
		if ($end_cycle > $result['maximum']) {
			$end_cycle = $result['maximum'];
		}

		// FIXME: this doesn't deal with holes very nicely - how to figure out which rings are holes ???

		for ($this->cycle = $start_cycle; $this->cycle <= $end_cycle; $this->cycle++) {
			$this->all = new ArrayCollection;
			$this->addInferiors($realm->getId());
			if ($this->all->isEmpty()) continue;

			$mvg = 'stroke red stroke-opacity 0.8 fill \''. $realm->getColourHex().'\' fill-opacity 0.6';
			$cmd = $this->base_cmd;

			// this is PostGIS specific:
			$rsm = new ResultSetMapping();
			$rsm->addScalarResult('poly', 'poly');
			$query = $this->em->createNativeQuery('select ST_AsSVG(ST_SnapToGrid(ST_Simplify(ST_Translate(ST_Scale(ST_Union(g.poly), 0.002, -0.002),0,1024),1),1)) as poly from geodata g where id in (select distinct settlement_id from statistic.settlement where realm_id IN (:all_subs) and cycle = :cycle)', $rsm);
			$query->setParameters(array('all_subs'=>$this->all->toArray(), 'cycle'=>$this->cycle));
			if ($result = $query->getResult()) {
				$poly = $result[0]['poly'];

				echo $cmd.' -draw "'.$mvg.' path \''.$poly.'\'" png24:'.$this->output_path.'realm-'. $realm->getId().'-'.sprintf('%1$05d', $this->cycle-$start_cycle).".png\n";
				echo "echo -n .\n";
			}

		}

		echo "ffmpeg -r 30 -qscale 2 -i realm-". $realm->getId()."-%05d.png realm-movie.mp4\n";
	}

	protected function addInferiors($realm_id): void {
		$this->all->add($realm_id);

		$query = $this->em->createQuery("SELECT r.id FROM App\Entity\StatisticRealm s join s.realm r JOIN s.superior x WHERE x.id = :me AND s.cycle = :cycle");
		$query->setParameters(array('me'=>$realm_id, 'cycle'=>$this->cycle));
		foreach ($query->getScalarResult() as $row) {
			$this->addInferiors($row['id']);
		}
	}
}
