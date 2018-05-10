<?php
/**
 * Created by PhpStorm.
 * User: michaelsilverman
 * Date: 4/23/18
 * Time: 4:22 PM
 */

namespace Drupal\srg_twilio\Event;


use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a incident report event for event subscribers.
 *
 * Whenever there is additional contextual data that you want to provide to the
 * event subscribers when dispatching an event you should create a new class
 * that extends \Symfony\Component\EventDispatcher\Event.
 *
 * See \Drupal\Core\Config\ConfigCrudEvent for an example of this in core.
 *
 * @ingroup events_example
 */
class SendTextEvent extends Event {

    /**
     * Number where message was sent.
     *
     * @var string
     */
    protected $to;


    /**
     * Text message.
     *
     * @var string
     */
    protected $text_message;

    /**
     * Constructs an incident report event object.
     *
     * @param string $to
     *   The incident report type.
     * @param string $from
     *   A detailed description of the incident provided by the reporter.
     */
    public function __construct($to, $text_message) {
        $this->to = $to;
        $this->text_message = $text_message;
    }

    /**
     * Get the To number.
     *
     * @return string
     */
    public function getTo() {
        return $this->to;
    }

    /**
     * Get the text
     *
     * @return string
     */
    public function getText() {
        return $this->text_message;
    }

}