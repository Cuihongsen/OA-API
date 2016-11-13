<?php

function week_to_date($variable, $start, $end)
{
    $date = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
    $Sat  = array();
    foreach ($variable as $key => $value) {
        // echo $date[$value];
        $first_Sat = date('Y-m-d', strtotime('this ' . $date[$value], strtotime($start)));
        $Sat[]     = $first_Sat;
        for ($i = 0;; $i += 7) {
            $current_time = strtotime($i . ' days', strtotime($first_Sat));
            if ($current_time > strtotime($end)) {
                break;
            }
            $Sat[] = date('Y-m-d', $current_time);
        }
    }
    return (array_unique($Sat));
}
