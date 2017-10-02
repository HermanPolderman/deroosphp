<?php
require_once __DIR__ . '/vendor/autoload.php';

define('APPLICATION_NAME', 'Gidsen de Roos');
define('CALENDAR_ID_MOLEN', '4rcgbukfq0mlsctmsovrvs6mbk@group.calendar.google.com');
define('CALENDAR_ID_GIDSEN', '4sm976uledkcg9id60sh0v599c@group.calendar.google.com');
define('CALENDAR_ID_MOLENAARS', 'btc2lluuat1vsqhrdv042g3g6o@group.calendar.google.com');

define('CREDENTIALS_PATH', __DIR__ . '/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');

// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

/**
 * Prints a list of events.
 * @param events to print.
 */
function printEvents($results) {
	if (count($results->getItems()) == 0) {
  		print "Niets gevonden.\n";
	} else {
  		foreach ($results->getItems() as $event) {
    			$start = $event->start->dateTime;
    			if (empty($start)) {
      				$start = $event->start->date;
    			}
    			printf("%s %s\n", substr($start,0,10), $event->getSummary());
  		}
	}
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);
$optParams = array(
  'maxResults' => 10,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => date('c'),
);

print "Molen:\n";
printEvents($service->events->listEvents(CALENDAR_ID_MOLEN, $optParams));
print "Gidsen:\n";
printEvents($service->events->listEvents(CALENDAR_ID_GIDSEN, $optParams));
print "Molenaars:\n";
printEvents($service->events->listEvents(CALENDAR_ID_MOLENAARS, $optParams));

$event = new Google_Service_Calendar_Event(array(
  'summary' => 'php event',
  'description' => 'php eventomschrijving.',
  'start' => array(
    'dateTime' => date('c'),
  ),
  'end' => array(
    'dateTime' => date('c'),
  ),
));


$event = $service->events->insert(CALENDAR_ID_MOLENAARS, $event);
printf('Event aangemaakt: %s\n', $event->htmlLink);

