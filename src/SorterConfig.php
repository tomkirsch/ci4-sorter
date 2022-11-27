<?php

namespace Tomkirsch\Sorter;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\I18n\Time;

class SorterConfig extends BaseConfig
{
    /**
     * Ensures dates from the DB (or Entity) are Time instances. 
     * Overwrite to provide your own Time Zone settings. NULL values are handled already.
     */
    public function getDate($time, string $field, $row): Time
    {
        $appConfig = config("app");
        if (!is_a($time, '\CodeIgniter\I18n\Time')) $time = new Time($time);
        if ($time->getUtc() && !empty($appConfig->localTimezone)) {
            $time = $time->setTimezone($appConfig->localTimezone);
        }
        return $time;
    }
}
