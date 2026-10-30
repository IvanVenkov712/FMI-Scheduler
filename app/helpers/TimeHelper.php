<?php
namespace App\Helpers;

class TimeHelper
{
    public static function is_valid_interval($start_time, $end_time)
    {
        $start_timestamp = strtotime((string) $start_time);
        $end_timestamp = strtotime((string) $end_time);

        return $start_timestamp !== false
            && $end_timestamp !== false
            && $start_timestamp < $end_timestamp;
    }

    /** Strict overlap permits one booking to start exactly when another ends. */
    public static function intervals_overlap($left_start, $left_end, $right_start, $right_end)
    {
        return strtotime((string) $left_start) < strtotime((string) $right_end)
            && strtotime((string) $left_end) > strtotime((string) $right_start);
    }
}
