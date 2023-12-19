<?php

namespace App\Entity;

/**
 * ActivityReportObserver
 */
class ActivityReportObserver {


	private int $id;
	private ActivityReport $activity_report;
	private Character $character;

	public function setReport($report = null): ActivityReportObserver|static {
		return $this->setActivityReport($report);
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
	 * Set activity_report
	 *
	 * @param ActivityReport|null $activityReport
	 *
	 * @return ActivityReportObserver
	 */
    public function setActivityReport(ActivityReport $activityReport = null): static {
        $this->activity_report = $activityReport;

        return $this;
    }

    /**
     * Get activity_report
     *
     * @return ActivityReport
     */
    public function getActivityReport(): ActivityReport {
        return $this->activity_report;
    }

    /**
     * Set character
     *
     * @param Character|null $character
     *
     * @return ActivityReportObserver
     */
	public function setCharacter(Character $character = null): static {
        $this->character = $character;

        return $this;
    }

    /**
     * Get character
     *
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character;
    }
}
