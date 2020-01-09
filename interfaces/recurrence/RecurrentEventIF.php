<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\interfaces\recurrence;


use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;

/**
 * This interface is used for event types supporting recurrent events.
 *
 * ## Recurrent event model
 *
 * Ideally a recurrent event type should implement the following fields:
 *
 * - `id` the event id
 * - `start_datetime` as datetime field defining the start of the event
 * - `end_datetime` as datetime field defining the start of the event
 * - `rrule` a rrule string
 * - `parent_event_id` the id of the recurring root event
 *
 * ## Recurrent root event
 *
 * A recurrent event consists of a root even, which itself is never displayed in the calendar but is rather used to create
 * recurrent event instances depending on its `rrule`. This means the first instance of a recurrent event has the same
 * start as the root event (until its edited).
 *
 * An root event has the following characteristics:
 *
 * - [[getRrule()]] returns not null
 * - [[getRecurrenceRootId()]] returns null
 * - [[getRecurrenceId()]] returns null
 *
 * while a recurrence instance has the following characteristics:
 *
 * - [[getRrule()]] returns the same rrule as the root event
 * - [[getRecurrenceRootId()]] returns the root event id
 * - [[getRecurrenceId()]] returns a recurrence id
 *
 * ## Expand recurrent event
 *
 * A recurrent event can be expanded as follows:
 *
 * ```
 * $event->getEventQuery()->expandRoot($from, $to, $save);
 * ```
 *
 * This will expand the $event root by creating recurrent instances within the $from, $to date range. The third parameter
 * defines if those instances should be saved automatically. This should be avoided when fetching many events.
 *
 * Ideally recurrent event instances are only persisted on demand, e.g. when accessing the event.
 * Single recurrence instances can be expanded as follows:
 *
 * ```
 * $event->getEventQuery()->expandSingle($recurrence_id);
 * ```
 *
 * This will expand and save a single recurrence instance with the given $recurrence_id.
 *
 * ## Edit recurrent event
 *
 * Editing a recurrent event is a bit more complex than editing a regular event. Recurrent events usually facilitate the
 * [[RecurrenceFormModel]] which supports the following edit modes:
 *
 * - Edit this event: Does only affect the current instance
 * - Edit this and following events: This will split the recurrent event into two separate recurrent events
 * - Edit all: This will edit the root event and sync all existing instances
 *
 * ## Query
 *
 * The query returned by [[getEventQuery()]] needs to extend [[AbstractRecurrenceQuery]] and supports some additional recurrence
 * related functions as:
 *
 * - [[AbstractRecurrenceQuery::getFollowingInstances()]]
 * - [[AbstractRecurrenceQuery::getExistingRecurrences()]]
 * - [[AbstractRecurrenceQuery::getExistingRecurrences()]]
 * - [[AbstractRecurrenceQuery::getRecurrenceRoot()]]
 * - [[AbstractRecurrenceQuery::getRecurrenceInstance()]]
 * - [[AbstractRecurrenceQuery::expandRoot()]]
 * - [[AbstractRecurrenceQuery::expandSingle()]]
 *
 * @package humhub\modules\calendar\interfaces\recurrence
 */
interface RecurrentEventIF extends CalendarEventIF
{
    /**
     * @return int the id of this event
     */
    public function getId();

    /**
     * @return string the rrule string
     */
    public function getRrule();

    /**
     * Sets the $rrule string of this event.
     *
     * @param string|null $rrule
     */
    public function setRrule($rrule);

    /**
     * Returns the id of the recurrent root event.
     *
     * > Note: The root id should not be set manually
     *
     * @return int the id of the root event
     */
    public function getRecurrenceRootId();

    /**
     * Sets the id of the recurrence root event.
     *
     * > Note: The root id should not be set manually.
     *
     * @param int $rootId sets the root id of a recurrence instance
     */
    public function setRecurrenceRootId($rootId);

    /**
     * Returns the recurrence id of this event.
     *
     * > Note: The recurrence id should not be set manually
     *
     * @return string|null
     */
    public function getRecurrenceId();

    /**
     * Sets the recurrence id.
     *
     * > Note: The recurrence id should not be set manually
     *
     * @param $recurrenceId
     * @return mixed
     */
    public function setRecurrenceId($recurrenceId);

    /**
     * Returns a comma seperated list of recurrence ids, which are used to exclude specific dates from the recurring
     * root event.
     *
     * > Note: Exdates are set automatically in the beforeDelete handler of ContentActiveRecord based events.
     *
     * @return string|null
     */
    public function getExdate();

    /**
     * Sets the exdate field, which are used to exclude specific dates from the recurring root event.
     *
     * In order to create an exdate string the [[RecurrenceHelper::addExdates()]] can be used.
     *
     * > Note: Exdates are set automatically in the beforeDelete handler of ContentActiveRecord based events.
     *
     * @param $exdateStr
     * @return mixed
     * @see RecurrenceHelper::addExdates()
     */
    public function setExdate($exdateStr);

    /**
     * @return AbstractRecurrenceQuery
     */
    public function getEventQuery();

    /**
     * This function should synchronize the data of this event with the given $root event, by copying all data and
     * content as title description texts and additional model data.
     *
     * Here are examples of data which should be copied:
     *
     * - content owner [[Content::$created_by]]
     * - title
     * - description
     * - timezone
     * - all_day
     *
     * The following data is copied by default and therefore does not have to be copied manually:
     *
     * - uid
     * - recurrence root id
     * - rrule
     *
     * The following data should not be copied:
     *
     *  - id
     *  - start_datetime
     *  - end_datetime
     *  - recurrence_id
     *
     * @param $root static
     * @return mixed
     */
    public function syncEventData($root);

    /**
     * This function is called while expanding a recurrent root event.
     * Usually the implementation of this event sets the `start_datetime` and `end_datetime` and
     * calls [[syncEventData()]].
     *
     * Example:
     *
     * ```php
     * public function createRecurrence($start, $end)
     * {
     *   $instance = new self($this->content->container, $this->content->visibility);
     *   $instance->start_datetime = $start;
     *   $instance->end_datetime = $end;
     *   $instance->syncEventData($this);
     *   return $instance;
     * }
     * ```
     *
     * @param string $start start of the event in db format
     * @param string $end end of the event in db format
     * @return mixed
     */
    public function createRecurrence($start, $end);
}