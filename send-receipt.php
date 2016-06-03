<?php
require_once('vendor/autoload.php');
use Mailgun\Mailgun;
\Stripe\Stripe::setApiKey("your stripe code");
// retrieve the request's body and parse it as JSON
$body = @file_get_contents('php://input');
$event_json = json_decode($body);
// received event
http_response_code(200);
// for extra security, retrieve from the Stripe API
$event_id = $event_json->id;
$event = \Stripe\Event::retrieve($event_id);
// This will send receipts on successful charges
if ($event->type == 'charge.succeeded') {
  email_receipt($event->data->object);
}
function email_receipt($charge) {
  $customer = \Stripe\Customer::retrieve($charge->customer);
  $subject = 'Payment Receipt/Getting Started';
  $bodyofmessage = message_body($charge, $customer);
  
  // Instantiate the client.
  $client = new \Http\Adapter\Guzzle6\Client();
  $mailgun = new \Mailgun\Mailgun('your Mailgun key', $client);
  $domain = "yourdomain.com";
  // Make the call to the client.
  $result = $mailgun->sendMessage($domain, array(
    'from'    => 'My Software Company <noreply@yourdomain.com>',
    'to'      => $customer->email,
    'subject' => $subject,
    'text'    => $bodyofmessage
  ));
}
function generate_license_key($customer) {
  $name = escapeshellarg("None None");
  $email = escapeshellarg($customer->email);
  $expiration_time_in_days = 365 * 100;
  $command = escapeshellcmd(sprintf("/srv/license-server/src/generate_product_key.sh %s %s %s %s", $name, $email, $expiration_time_in_days));
  exec($command, $output, $return);
  return $output[0];
}
function message_body($charge, $customer) {
  $productkey = generate_license_key($customer);
  return <<<EOF
Hi!
Thanks for buying my software!
Download my software for your platform here:
www.yourdomain.com/downloads
Use this product key: $productkey
If you have any questions, please email us at contact@yourdomain.com
-- My Team
My Software Company
EOF;
}
?>
