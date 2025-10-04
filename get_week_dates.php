<?php
// get_week_dates.php

/**
 * Returns the start and end dates of the week for a given date.
 * Assumes the week starts on Monday.
 *
 * @param DateTime $date The date to find the week for.
 * @return array An array with 'start' and 'end' keys.
 */
function getWeekRange(DateTime $date) {
    $startDate = clone $date;
    $startDate->modify('monday this week');

    $endDate = clone $startDate;
    $endDate->modify('sunday this week');

    return [
        'start' => $startDate->format('Y-m-d'),
        'end' => $endDate->format('Y-m-d'),
    ];
}

/**
 * Returns the specific date for a given day of the week within the current week.
 *
 * @param string $dayOfWeek The English name of the day (e.g., 'Monday', 'Tuesday').
 * @return string The date in 'Y-m-d' format.
 */
function getDateForDayOfWeek($dayOfWeek) {
    $date = new DateTime();
    $date->modify($dayOfWeek . ' this week');
    return $date->format('Y-m-d');
}
?>
