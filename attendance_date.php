<?php
function isAttendanceDateAllowed($eventStartTime)
{
    if (!is_string($eventStartTime) || strlen($eventStartTime) < 10) {
        return false;
    }

    $eventDate = substr($eventStartTime, 0, 10);
    return $eventDate >= date('Y-m-d', strtotime('-1 day'));
}
