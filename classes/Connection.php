<?php

namespace jeyemwey\T2C;

/**
 * A connection contains any information for riding a train.
 * @see http://transport.opendata.ch/docs.html
 */
class Connection {

	/**
	 * @var $from string Station name of departure
	 * @var $to string Station name of arrival
	 */
	public $from_location;
	public $to_location;

	/** @var $sections Array<Section> Any train part of a connection. */
	public $sections = [];

	/** @var $UCID string Unique Combined IDentifier */
	public $UCID = "";

	/**
	 * Constructor
	 * Maps all the API data to this object.
	 * @param $json_connection StdClass API data.
	 * @return void
	 */
	public function __construct($json_connection) {
		$this->from_location = $json_connection->from->station->name;
		$this->to_location = $json_connection->to->station->name;

		$this->sections = array_map(function($section) {
			return new Section($section);
		}, $json_connection->sections);

		$this->UCID = $this->getUniqueTrainNumbers();
		$this->Calendar = $this->buildCalendar();
	}

	/**
	 * Build Unique Connection Id for one day.
	 * Uses the train numbers to identify itsself.
	 * @return string All the train numbers in one hash.
	 */
	private function getUniqueTrainNumbers() {
		$connectionHash = "";
		foreach ($this->sections as $section) {
			$connectionHash .= $section->trainnumber;
		}
		return str_replace(' ', '', $connectionHash);
	}

	/**
	 * Build Calendar from this Connection.
	 * @see Eluceo\iCal
	 * @return string The rendered Calendar in ICS.
	 */
	public function buildCalendar() {
		$vCalender = new \Eluceo\iCal\Component\Calendar("http://www.bahn.de");
		#H::CalendarSetTimeZones($vCalender);

		foreach ($this->sections as $section) {
			$event = new \Eluceo\iCal\Component\Event();
			$event->setDtStart($section->from_time)
				 ->setDtEnd($section->to_time)
				 //->setUseTimezone(1)
				 ->setSummary("🚄" . $section->trainnumber . ": ". $section->from_location
				 	. " (pl. " . $section->from_platform . ") ➡️ " . $section->to_location . " (pl. " . $section->from_platform . ")")
				 ->setDescription("All information is issued without liability. Subject to timetable changes. Please check the up-to-date timetable shortly before the travel date at www.bahn.de.");
			$vCalender->addComponent($event);
		}

		return $vCalender->render();
	}
}