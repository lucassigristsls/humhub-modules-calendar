<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models\fullcalendar;


use humhub\modules\calendar\interfaces\fullcalendar\FullCalendarEventIF;
use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\calendar\models\CalendarEntry;
use Yii;
use DateTime;
use Exception;
use humhub\modules\calendar\interfaces\event\CalendarEventStatusIF;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\libs\Html;
use yii\base\InvalidConfigException;

class FullCalendar
{
    /**
     * @param CalendarEntry $entry
     * @return array
     * @throws Exception
     */
    public static function getFullCalendarArray(CalendarEventIF $entry)
    {
        $calendarService = new CalendarService();

        $result = [
            'uid' => $entry->getUid(),
            'title' => static::getTitle($entry),
            'editable' => false,
            'backgroundColor' => Html::encode($calendarService->getEventColor($entry)),
            'allDay' => $entry->isAllDay(),
            'viewUrl' => $entry->getUrl(),
            'viewMode' => FullCalendarEventIF::VIEW_MODE_REDIRECT,
            'icon' => $entry->getIcon(),
            'start' => static::toFullCalendarFormat($entry->getStartDateTime(), $entry->isAllDay()),
            'end' => static::toFullCalendarFormat(static::getEndDate($entry), $entry->isAllDay()),
            'eventDurationEditable' => true,
            'eventStartEditable' => true
        ];

        if($entry instanceof FullCalendarEventIF) {
            $result['editable'] = $entry->isUpdatable();
            $result['updateUrl'] = $entry->getUpdateUrl();
            $result['viewMode'] = $entry->getCalendarViewMode();
            $result['viewUrl'] = $entry->getCalendarViewUrl();
            $extraOptions = $entry->getFullCalendarOptions();
            if(!empty($extraOptions)) {
                $result = array_merge($result, $extraOptions);
            }
        }

        if($entry instanceof RecurrentEventIF) {
            $result['rrule'] = $entry->getRrule();
            $result['exdate'] = $entry->getExdate();
        }

        return $result;
    }

    private static function getEndDate(CalendarEventIF $entry)
    {
        $endDateTime = clone $entry->getEndDateTime();

        if($entry->isAllDay()) {
            // Note: In fullcalendar the end time is the moment AFTER the event.
            // But we store the exact event time 00:00:00 - 23:59:59 so add some time to the full day event.
            $endDateTime->add(new \DateInterval('PT2H'))->setTime(0,0,0);
        }

        return $endDateTime;
    }

    private static function getTitle(CalendarEventIF $entry)
    {
        $title = $entry->getTitle();

        if($entry instanceof CalendarEventStatusIF && $entry->getEventStatus() === CalendarEventStatusIF::STATUS_CANCELLED) {
            $title .= ' ('.Yii::t('CalendarModule.base', 'canceled').')';
        }

        return $title;
    }

    /**
     * @param DateTime $dt
     * @param bool $allDay
     * @return string
     * @throws InvalidConfigException
     */
    public static function toFullCalendarFormat(DateTime $dt, $allDay = false)
    {
        return CalendarDateFormatter::formatDate($dt, 'php:c', $allDay);
    }
}