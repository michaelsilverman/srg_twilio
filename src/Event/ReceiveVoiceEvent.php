<?php
/**
 * Created by PhpStorm.
 * User: michaelsilverman
 * Date: 4/23/18
 * Time: 4:22 PM
 */

namespace Drupal\srg_twilio\Event;


use Drupal\twilio\Entity\TwilioSMS;
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
class ReceiveVoiceEvent extends Event {

    /**
     * Number from where message was received.
     *
     * @var TwilioSMS
     */
    protected $sms_package;


    /**
     * Constructs an incident report event object.
     *
     * @param TwilioSMS $sms_package
     *   The incident report type.

     */
    public function __construct($sms_package) {
        $this->sms_package = $sms_package;
    }

    /**
     * Get the text
     *
     * @return TwilioSMS
     */
    public function getPackage() {
        return $this->sms_package;
    }

}