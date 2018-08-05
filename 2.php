<?php

class NextWorkingDay
{
    /**
     * @var Datetime
     */
    protected $date;

    /**
     * Порялковый номер субботы в неделе
     */
    const SATURDAY = 6;

    /**
     * Порялковый номер воскрсенья в неделе
     */
    const SUNDAY = 0;

    /**
     * Выходные
     */
    const WEEKEND = [self::SATURDAY, self::SUNDAY];

    /**
     * Шаблон приведения даты к строке
     */
    const DATE_FORMAT = 'Y_m_d';

    /**
     * Список всех федеральные  праздников
     */
    const HOLIDAYS = [
        // Новый год
        'first day of january',
        // День Мартина Лютера Кинга
        'third monday of january',
        // День инаугурации
        '20-jan',
        // Президентский день
        'third monday of february',
        // День памяти
        'last  monday of may',
        // День независимости
        '4-Jul',
        // День Труда
        'first monday of september',
        // День Колумба
        'second monday of october',
        // День ветеранов
        '11-nov',
        // День благодарения
        'fourth thursday of november',
        //Рождество
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
