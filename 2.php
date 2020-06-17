<?php

class NextWorkingDay
{
    /**
     * @var Datetime
     */
    protected $date;

    const SATURDAY = 6;

    const SUNDAY = 0;

    const WEEKEND = [self::SATURDAY, self::SUNDAY];

    const DATE_FORMAT = 'Y_m_d';

    const HOLIDAYS = [
        // New Year
        'first day of january',
        // Martin Luther King Day
        'third monday of january',
        // Inauguration Day
        '20-jan',
        // Presidential day
        'third monday of february',
        // Day of Remembrance
        'last  monday of may',
        // independence Day
        '4-Jul',
        // Labor Day
        'first monday of september',
        // Columbus Day
        'second monday of october',
        // Veterans Day
        '11-nov',
        // Thanksgiving Day
        'fourth thursday of november',
        // Christmas
        '25-dec',
    ];

    /**
     * @var array[\DateTime]
     */
    protected $nonWorkingDays = [];

    /**
     * OrderDateSend constructor.
     *
     * @param \Datetime $currentDay
     */
    public function __construct(\DateTime $currentDay)
    {
        $this->date = $currentDay;

        $this->prepareNonWorkingDays();
    }

    /**
     * @param int $days
     *
     * @return \DateTime
     */
    public function getNextWorkingDayVia(int $days): \DateTime
    {
        $date = clone $this->date;

        while ($days) {
            $date->modify('+1 day');

            while (!$this->isWorkingDay($date)) {
                $date->modify('+1 day');
            }

            $days--;
        }

        return $date;
    }

    /**
     * @param \DateTime $date
     *
     * @return bool
     */
    protected function isWorkingDay(\DateTime $date): bool
    {
        $dayNumberOfWeek = intval($date->format('w'));

        return !in_array($dayNumberOfWeek, self::WEEKEND) &&
            !in_array($date->format(self::DATE_FORMAT), $this->nonWorkingDays);
    }

    /**
     * @param int|null $year
     */
    protected function prepareNonWorkingDays($year = null)
    {
        $currentMonth = $this->date->format('m');
        $currentYear = (int) $this->date->format('Y');
        $year = $year ?? $currentYear;

        while ($year <= $currentYear + 1) {
            foreach (self::HOLIDAYS as $index => $holiday) {
                $date = new \DateTime($holiday . ' ' . $year);
                $dayNumberOfWeek = (int) $date->format('w');

                if ($dayNumberOfWeek === self::SATURDAY) {
                    $date->modify('-1 day');
                } else if ($dayNumberOfWeek === self::SUNDAY) {
                    $date->modify('+1 day');
                }

                if (($date->format('m') < $currentMonth && $year === $currentYear) ||
                    $date->format('m') >= $currentMonth && $year !== $currentYear
                ) {
                    continue;
                }

                $this->nonWorkingDays[] = $date->format(self::DATE_FORMAT);
            }

            $year++;
        }
    }
}
