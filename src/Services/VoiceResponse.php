<?php

namespace Drupal\twilio\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\twilio\Event\SendTextEvent;
use Drupal\twilio\Event\TwilioEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\twilio\Services\Command;

/**
 * Service class for Twilio API commands.
 */
class VoiceRespone {

//  private $sid;
//  private $token;
//  private $number;
  protected $event_dispatcher;





    /*
     * @param LoggerChannelFactoryInterface $loggerFactory
     */
    protected  $loggerFactory;

    public function __construct(Command $command, LoggerChannelFactoryInterface $loggerFactory) {
        $this->command = $command;
        $this->loggerFactory = $loggerFactory;
    }
    public static function create(ContainerInterface $container) {
        $command = $container->get('srg_twilio.command');
        $loggerFactory = $container->get('logger.factory');
        return new static($command, $loggerFactory);
    }





  /**
   * Get the Twilio Account SID.
   *
   * @return string
   *   The configured Twilio Account SID.
   */
  public function getSid() {
    if (empty($this->sid)) {
      $this->sid = \Drupal::config('twilio.settings')->get('account');
    }
    return $this->sid;
  }



  /**
   * Get the Twilio Auth Token.
   *
   * @return string
   *   The configured Twilio Auth Token.
   */
  public function getToken() {
    if (empty($this->token)) {
      $this->token = \Drupal::config('twilio.settings')->get('token');
    }
    return $this->token;
  }

  /**
   * Get the Twilio Number.
   *
   * @return string
   *   The configured Twilio Number.
   */
  public function getNumber() {
    if (empty($this->number)) {
      $this->number = \Drupal::config('twilio.settings')->get('number');
    }
    return $this->number;
  }

  /**
   * Send an SMS message.
   *
   * @param string $number
   *   The number to send the message to.
   * @param string|array $message
   *   Message text or an array:
   *   [
   *     from => number
   *     body => message string
   *     mediaUrl => absolute URL
   *   ].
   */
  public function messageSend(string $number, $message) {
    if (is_string($message)) {
      $message = [
        'from' => $this->number,
        'body' => $message,
      ];
    }
    $sid = $this->command->getSid();
    $auth = $this->command->getToken();
    $number = $this->command->getNumber();
    $message['from'] = !empty($message['from']) ? $message['from'] : $this->number;
    if (empty($message['body'])) {
      throw new TwilioException("Message requires a body.");
    }
    if (!empty($message['mediaUrl']) && !UrlHelper::isValid($message['mediaUrl'], TRUE)) {
      throw new TwilioException("Media URL must be a valid, absolute URL.");
    }
    $client = new Client($this->sid, $this->token);
    $client->messages->create($number, $message);

  }

    /**
     * Make a voice call.
     *
     * @param string $number
     *   The number to send the message to.
     * @param string|array $message
     *   Message text or an array:
     *   [
     *     from => number
     *     body => message string
     *     mediaUrl => absolute URL
     *   ].
     */
    public function voiceCall(string $from_number='+19192960477', $to_number='+16308999711', $message) {

        $client = new Client($this->getSid(), $this->getToken());
        $call = $client->calls->create(
            $this->getNumber(), $to_number,
            array("url" => "http://demo.twilio.com/docs/voice.xml")
        );
        $event = new SendTextEvent($to_number, $message);

        // Dispatch an event by specifying which event, and providing an event
        // object that will be passed along to any subscribers.
        $dispatcher = \Drupal::service('event_dispatcher');
        $dispatcher->dispatch(TwilioEvents::SEND_TEXT_EVENT, $event);
        echo $call->sid;
    }


  /**
   * Check number if landline or mobile, also get carrier
   *
   * @param string $number
   *   The number to send the message to.
   * @param array $type
   *   [
   *     type => array('x', 'y', 'z')
   *   ].
   */

    public function checkNumber(string $number, array $type)
    {
       $client = new Client($this->sid, $this->token);
        $number_info = $client->lookups
            ->phoneNumbers($number)
            ->fetch($type);
        return $number_info;
    }

}
