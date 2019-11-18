<?php
/*
    ! PROOF OF CONCEPT !

    This script gets contents of emails and insert them into a new task log
    dependent on the tag of an email. e.g. an email sent to
    dotprojectemail+1234@gmail.com will add a task log for the task with
    task_id=1234.

    ? Currently this script has to be run from the command line.

    Required files not included in commit:
    - credentials.json - google developer credentials
    - token.json - will be created automatically
*/
require_once 'base.php';
require_once DP_BASE_DIR.'/includes/config.php';
require_once DP_BASE_DIR.'/includes/main_functions.php';
require_once DP_BASE_DIR.'/includes/db_connect.php';
require_once DP_BASE_DIR.'/classes/ui.class.php';
require_once DP_BASE_DIR.'/classes/event_queue.class.php';
require_once DP_BASE_DIR.'/classes/query.class.php';
$AppUI = new CAppUI;
$AppUI->setUserLocale();
$perms =& $AppUI->acl();
require_once($AppUI->getLibraryClass('google-api-php-client-2.2.1/vendor/autoload'));
require_once($AppUI->getModuleClass('tasks'));

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    // ! Proxy settings for DBW
    // $client->setHttpClient(new GuzzleHttp\Client([
    //     'proxy' => 'proxy1:3128',
    //     'verify' => false,
    // ]));
    $client->setApplicationName('Gmail API PHP Quickstart');
    $client->setScopes(Google_Service_Gmail::GMAIL_READONLY);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

$client = getClient();
$service = new Google_Service_Gmail($client);
$user = 'me';
$results = $service->users_messages->listUsersMessages($user);

/*
    Decodes body of email
*/
function decodeBody($body) {
    $rawData = $body;
    $sanitisedData = strtr($rawData, '-_', '+/');
    $decodedMessage = base64_decode($sanitisedData);
    if (!$decodedMessage) {
        $decodedMessage = FALSE;
    }
    return $decodedMessage;
}

try {
    while ($results->getMessages() != null) {
        foreach($results->getMessages() as $mlist) {
            $message_id = $mlist->id;
            $optParamsGet2['format'] = 'full';
            $single_message = $service->users_messages->get($user, $message_id, $optParamsGet2);

            $payload = $single_message->getPayload();

            $body = $payload->getBody();
            $headers = $payload->getHeaders();

            $FOUND_BODY = decodeBody($body['data']);
            $subject = array_values(array_filter($headers, function($k) {
                return $k['name'] == 'Subject';
            }));
            $addresses = array_values(array_filter($headers, function($k) {
                return $k['name'] == "To";
            }));

            if (!$FOUND_BODY) {
                $parts = $payload->getParts();
                foreach ($parts as $part) {
                    if ($part['body']) {
                        $FOUND_BODY = decodeBody($part['body']->data);
                        break;
                    }

                    if ($part['parts'] && !$FOUND_BODY) {
                        foreach ($part['parts'] as $p) {
                            if ($p['mimeType'] === 'text/html' && $p['body']) {
                                $FOUND_BODY = decodeBody($p['body']->data);
                                break;
                            }
                        }
                    }

                    if ($FOUND_BODY) {
                        break;
                    }
                }
            }

            $address = $addresses[0]->getValue();
            if (strpos($address, "+") !== false) {
                // Formatting address address to get task_id
                $pos1 = strpos($address, "+")+1;
                $pos2 = strpos($address, "@");
                $length = abs($pos1 - $pos2);
                $task_id = substr($address, $pos1, $length);
                
                // Task log creation
                $log = new CTaskLog();
                $log->task_log_task = intval($task_id);
                $log->task_log_name = $subject[0]->getValue();
                $log->task_log_description= $FOUND_BODY;
                $log->task_log_creator = 285;
                $log->task_log_hours = 1;
                $log->task_log_costcode = 1;
                $log->store();
            }
        }
        if ($results->getNextPageToken() != null) {
            $pageToken = $list->getNextPageToken();
            $list = $gmail->users_messages->listUsersMessages($user, ['pageToken' => $pageToken, 'maxResults' => 1000]);
        } else {
            break;
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

?>