<?php

namespace Drupal\srg_twilio\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\twilio\Entity\TwilioSMS;
use Twilio\Rest\Client;
use Drupal\twilio\Event\ReceiveTextEvent;
use Drupal\twilio\Event\ReceiveVoiceEvent;
use Drupal\twilio\Event\TwilioEvents;
use Twilio\Twiml;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\srg_twilio\Services\Command;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the twilio module.
 */
class SRGTwilioController extends ControllerBase {
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
   * Handle incoming SMS message requests.
   *
   * @todo Needs Work.
   */
  public function receiveText() {

      $twilio_message = new TwilioSMS($_REQUEST);
    if (!empty($twilio_message->getFrom()) && !empty($twilio_message->getMessage()) && !empty($twilio_message->getCountry())) { //} && twilio_command('validate')) {

        $codes = $this->countryDialCodes();
        $dial_code = $this->countryIsoToDialCodes($_REQUEST['ToCountry']);

        $number = SafeMarkup::checkPlain(str_replace('+' . $dial_code, '', $twilio_message->getFrom()));
        $number_twilio = !empty($_REQUEST['To']) ? SafeMarkup::checkPlain(str_replace('+', '', $twilio_message->getTo())) : '';
        $message = SafeMarkup::checkPlain(htmlspecialchars_decode($twilio_message->getMessage(), ENT_QUOTES));


        if (empty($codes[$dial_code])) {
            $this->loggerFactory->get('twilio')->debug(
            //    $this->logger('Twilio')->notice(
                'A message was blocked from the country @country, due to your currrent country code settings.',
                ['@country' => $_REQUEST['ToCountry']]
            );
            $markup = [
                '#markup' => 'error on county code',
                //   '#attached' => ['library' => ['client/index.custom']] ,
            ];
            return $markup;
        }
        // @todo: Support more than one media entry.
        $media = !empty($_REQUEST['MediaUrl0']) ? $_REQUEST['MediaUrl0'] : '';
        $options = [];
        // Add the receiver to the options array.
        if (!empty($_REQUEST['To'])) {
            $options['receiver'] = SafeMarkup::checkPlain($_REQUEST['To']);
        }
    }
        $this->loggerFactory->get('twilio')->debug
        ('An SMS message was sent from @number containing the message "@message"', [
            //    $this->logger('Twilio')->notice('An SMS message was sent from @number containing the message "@message"', [
            '@number' => $number,
            '@message' => $message,
        ]);

        // Dispatch an event by specifying which event, and providing an event
        // object that will be passed along to any subscribers.
        // $twilio_message = new TwilioSMS($message);

        $event = new ReceiveTextEvent($twilio_message);
        $dispatcher = \Drupal::service('event_dispatcher');
        $dispatcher->dispatch(TwilioEvents::RECEIVE_TEXT_EVENT, $event);
      $markup = [
          '#markup' => 'SMS Message receivedxxx',
      ];

      return $markup;
  }

  /**
   * Handle incoming voice requests.
   *
   * @todo Needs Work.
   */
  public function receiveVoice() {
      $twilio_message = new TwilioSMS($_REQUEST);
      $event = new ReceiveVoiceEvent($twilio_message);
      $dispatcher = \Drupal::service('event_dispatcher');
      $dispatcher->dispatch(TwilioEvents::RECEIVE_VOICE_EVENT, $event);

      $response = new Response();
      $twiml = new Twiml();
      $twiml->say('Hello World');
      $gather = $twiml->gather(['input' => 'speech dtmf', 'timeout' => 3,
          'numDigits' => 1, 'action' => '/twilio/test1']);
      $gather->say('Please press 1 or say sales for sales.');

      $response->setContent($twiml);
      return $response;

}
    /**
   * Invokes twilio_message_incoming hook.
   *
   * @param string $number
   *   The sender's mobile number.
   * @param string $number_twilio
   *   The twilio recipient number.
   * @param string $message
   *   The content of the text message.
   * @param array $media
   *   The absolute media url for a media file attatched to the message.
   * @param array $options
   *   Options array.
   */
  public function xxmessageIncoming($number, $number_twilio, $message, array $media = array(), array $options = array()) {
    // Build our SMS array to be used by our hook and rules event.
    $sms = array(
      'number' => $number,
      'number_twilio' => $number_twilio,
      'message' => $message,
      'media' => $media,
    );
    // Invoke a hook for the incoming message so other modules can do things.
    $this->moduleHandler()->invokeAll('twilio_message_incoming', [$sms, $options]);
    if ($this->moduleHandler()->moduleExists('rules')) {
      rules_invoke_event('twilio_message_incoming', $sms);
    }
  }

  /**
   * Invokes twilio_voice_incoming hook.
   *
   * @param string $company_name
   *   The sender's company name.

   */
  public function getCompany() {
      $client_name = 'Aluminum Company';
      $fred = new Command($client_name);
      $number = $fred->getNumber();
      $token = $fred->getToken();
      $sid = $fred->getSid();
      $markup = [
          '#markup' => $number.' '.$token.' '.$sid,
      ];
      return $markup;

  }

    /**
     * Invokes twilio_voice_incoming hook.
     *
     * @param string $company_name
     *   The sender's company name.

     */

    // not going to use this one
    public function sendText() {
        $client_name = 'Aluminum Company';
        $client_info = new Command($client_name);
        $message = getMessage($messageID);

    // Get message to send
        $client_info->messageSend('6308999711', 'fred', $image_url);
        $number = $client_info->getNumber();
        $token = $client_info->getToken();
        $sid = $client_info->getSid();
        $markup = [
               '#markup' => 'Text send to: '.$number.' '.$token.' '.$sid,
        ];
        return $markup;
    }

}
