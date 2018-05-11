<?php

namespace Drupal\srg_twilio\Services;

use Drupal\srg_twilio\Event\SendVoiceEvent;
use Twilio\Rest\Client;
use Twilio\Twiml;
use Twilio\Exceptions\TwilioException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\srg_twilio\Event\SendTextEvent;
use Drupal\srg_twilio\Event\TwilioEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Service class for Twilio API commands.
 */
class Command {

  private $sid;
  private $token;
  private $provider;
  private $number;
  protected $event_dispatcher;

    /**
     * Send an SMS message.
     *
     * @param string $client_name
     *   The number to send the message to.
     */
  public function __construct($client_name) {
  //    $client_name='Aluminum Company';
      $query = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('type', 'sms_company')
          ->condition('title', $client_name, '=');
      $nids = $query->execute();
      dpm($nids, 'nids');
      $node = node_load(reset($nids));
      $this->sid = $node->get('field_sms_account_sid');
      $this->provider = $node->get('field_sms_provider');
      $this->number = $node->get('field_sms_phone_number');
      $this->token = $node->get('field_sms_token_id');
  }


    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('event_dispatcher')
        );
    }





  /**
   * Get the Twilio Auth Token.
   *
   * @return string
   *   The configured Twilio Auth Token.
   */
  public function getToken() {
    return $this->token->getValue()[0]['value'];
  }

  /**
   * Get the Twilio Number.
   *
   * @return string
   *   The configured Twilio Number.
   */
  public function getSID() {
    return $this->sid->getValue()[0]['value'];
  }

  public function getProvider() {
     return $this->provider->getValue()[0]['value'];
  }

  public function getNumber() {
    return $this->number->getValue()[0]['value'];
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
  public function messageSend(string $number, $text, $image_url) {
    if (is_string($text)) {
      $message = [
        'from' => $this->getNumber(),
        'body' => $text,
        'mediaUrl' => $image_url,
      ];
    }
    $message['from'] = !empty($message['from']) ? $message['from'] : $this->number;
    if (empty($message['body'])) {
      throw new TwilioException("Message requires a body.");
    }
    if (!empty($message['mediaUrl']) && !UrlHelper::isValid($message['mediaUrl'], TRUE)) {
      throw new TwilioException("Media URL must be a valid, absolute URL.");
    }
    $client = new Client($this->getSID(), $this->getToken());
    $client->messages->create($number, $message);

    $event = new SendTextEvent($number, $message);

      // Dispatch an event by specifying which event, and providing an event
      // object that will be passed along to any subscribers.
      $dispatcher = \Drupal::service('event_dispatcher');
      $dispatcher->dispatch(TwilioEvents::SEND_TEXT_EVENT, $event);
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
    public function voiceCall(string $from_number, $to_number, $twiml_file) {

        $client = new Client($this->sid, $this->token);
  //      $client->calls->create($to_number, $from_number,
  //          array("url" => "http://demo.twilio.com/docs/voice.xml")
  //      );

   //     $twiml = new Twiml();
   //     $twiml->say('Hello World this is the message');
        $call = $client->calls->create(
            $to_number, $from_number,
        //    $twiml
             array("url" => "http://2ab4264d.ngrok.io/sites/default/files/twiml/".$twiml_file.".xml")
         //   array("url" => "http://demo.twilio.com/docs/voice.xml")
        );
        $event = new SendVoiceEvent($to_number, $twiml_file);

        // Dispatch an event by specifying which event, and providing an event
        // object that will be passed along to any subscribers.
        $dispatcher = \Drupal::service('event_dispatcher');
        $dispatcher->dispatch(TwilioEvents::SEND_VOICE_EVENT, $event);
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
