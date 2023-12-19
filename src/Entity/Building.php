<?php 

namespace App\Entity;

class Building {

	private float $workers;
	private bool $active;
	private int $focus;
	private int $condition;
	private int $resupply;
	private float $current_speed;
	private int $id;
	private Settlement $settlement;
	private Place $place;
	private BuildingType $type;

	public function startConstruction($workers): static {
   		$this->setActive(false);
   		$this->setWorkers($workers);
   		$this->setCondition(-$this->getType()->getBuildHours()); // negative value - if we reach 0 the construction is complete
   		return $this;
   	}

	public function getEmployees(): float|int {
   		// only active buildings use employees
   		if ($this->isActive()) {
   			$employees =
   				$this->getSettlement()->getFullPopulation() / $this->getType()->getPerPeople()
   				+
   				pow($this->getSettlement()->getFullPopulation() * 500 / $this->getType()->getPerPeople(), 0.25);
   
   			// as long as we have less than four times the min pop amount, increase the ratio (up to 200%)
   			if ($this->getType()->getMinPopulation() > 0 && $this->getSettlement()->getFullPopulation() < $this->getType()->getMinPopulation() * 4) {
   				$mod = 2.0 - ($this->getSettlement()->getFullPopulation() / ($this->getType()->getMinPopulation() * 4));
   				$employees *= $mod;
   			}
   			return ceil($employees * pow(2, $this->focus));
   		} else {
   			return 0;
   		}
   	}

	public function isActive(): bool {
   		return $this->getActive();
   	}

	public function abandon($damage = 1): static {
   		if ($this->isActive()) {
   			$this->setActive(false);
   			$this->setCondition(-$damage);
   		}
   		$this->setWorkers(0);
   		return $this;
   	}

	public function getDefenseScore(): float|int {
   		if ($this->getType()->getDefenses() <= 0) {
   			return 0;
   		} else  {
   			$worth = $this->getType()->getBuildHours();
   			if ($this->getActive()) {
   				$completed = 1;
   			} else {
   				$completed = abs($this->getCondition() / $worth);
   			}
   			return $this->getType()->getDefenses()*$completed;
   		}
   	}



    /**
     * Set workers
     *
     * @param float $workers
     *
     * @return Building
     */
    public function setWorkers(float $workers): static {
        $this->workers = $workers;

        return $this;
    }

    /**
     * Get workers
     *
     * @return float 
     */
    public function getWorkers(): float {
        return $this->workers;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Building
     */
    public function setActive(bool $active): static {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive(): bool {
        return $this->active;
    }

    /**
     * Set focus
     *
     * @param integer $focus
     *
     * @return Building
     */
    public function setFocus(int $focus): static {
        $this->focus = $focus;

        return $this;
    }

    /**
     * Get focus
     *
     * @return integer 
     */
    public function getFocus(): int {
        return $this->focus;
    }

    /**
     * Set condition
     *
     * @param integer $condition
     *
     * @return Building
     */
    public function setCondition(int $condition): static {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return integer 
     */
    public function getCondition(): int {
        return $this->condition;
    }

    /**
     * Set resupply
     *
     * @param integer $resupply
     *
     * @return Building
     */
    public function setResupply(int $resupply): static {
        $this->resupply = $resupply;

        return $this;
    }

    /**
     * Get resupply
     *
     * @return integer 
     */
    public function getResupply(): int {
        return $this->resupply;
    }

    /**
     * Set current_speed
     *
     * @param float $currentSpeed
     *
     * @return Building
     */
    public function setCurrentSpeed(float $currentSpeed): static {
        $this->current_speed = $currentSpeed;

        return $this;
    }

    /**
     * Get current_speed
     *
     * @return float 
     */
    public function getCurrentSpeed(): float {
        return $this->current_speed;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId(): int {
        return $this->id;
    }

	/**
	 * Set settlement
	 *
	 * @param Settlement|null $settlement
	 *
	 * @return Building
	 */
    public function setSettlement(Settlement $settlement = null): static {
        $this->settlement = $settlement;

        return $this;
    }

    /**
     * Get settlement
     *
     * @return Settlement
     */
    public function getSettlement(): Settlement {
        return $this->settlement;
    }

	/**
	 * Set place
	 *
	 * @param Place|null $place
	 * @return Building
	 */
    public function setPlace(Place $place = null): static {
        $this->place = $place;

        return $this;
    }

    /**
     * Get place
     *
     * @return Place
     */
    public function getPlace(): Place {
        return $this->place;
    }

	/**
	 * Set type
	 *
	 * @param BuildingType|null $type
	 * @return Building
	 */
    public function setType(BuildingType $type = null): static {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return BuildingType
     */
    public function getType(): BuildingType {
        return $this->type;
    }
}
