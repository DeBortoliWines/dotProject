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
// print_r("$service");
$user = 'me';
$labels = ['Label_4983233390187973438', "UNREAD"];
$results = $service->users_messages->listUsersMessages($user, ["labelIds" => $labels]);
// $results = $service->users_messages->listUsersMessages($user);
$allowedDomains = ["debortoli.com.au"];

/*
    Decodes body of email
*/
// function decodeBody($body) {
//     $rawData = $body;
//     $sanitisedData = strtr($rawData, '-_', '+/');
//     $decodedMessage = base64_decode($sanitisedData);
//     if (!$decodedMessage) {
//         $decodedMessage = FALSE;
//     }
//     return $decodedMessage;
// }

function decodeBody($data) {
    $data = base64_decode(str_replace(array('-', '_'), array('+', '/'), $data));
    // $data = imap_qprint($data);
    // print_r($data);
    return $data;
}

function findBody($parts, $type='text/plain') {
    foreach ($parts as $part) {
        if ($part['mimeType'] == $type)
            return $part->getBody()->getData();
        else {
            if ($part->getParts()) {
                $recurse = findBody($part->getParts());
                if (gettype($recurse) != 'array')
                    return $recurse;
            }
        }
    }
}

function getCcAddresses($headers) {
    foreach ($headers as $header) {
        if ($header->getName() == 'Cc')
            return $header->getValue();
    }
}

function getTags($addresses) {
    $tags = [];
    foreach ($addresses as $address) {
        if (strpos($address, '+') !== false) {
            $pos1 = strpos($address, '+')+1;
            $pos2 = strpos($address, '@');
            $length = abs($pos1 - $pos2);
            $tag = substr($address, $pos1, $length);
            $currentAddress = str_replace('+'.$tag, '', $address);
            if ($currentAddress == $projectAddress)
                array_push($tags, $tag);
        }
    }
    return $tags;
}

if (strpos($toAddress, "+") !== false) {
    $pos1 = strpos($toAddress, "+")+1;
    $pos2 = strpos($toAddress, "@");
    $length = abs($pos1 - $pos2);
    $taskId = substr($toAddress, $pos1, $length);
try {
        foreach($results->getMessages() as $mlist) {
            $message_id = $mlist->id;
            $optParamsGet2['format'] = 'full';
            $single_message = $service->users_messages->get($user, $message_id, $optParamsGet2);
            // print_r("MESSAGE ID: " . $single_message->id);

            $payload = $single_message->getPayload();
            // print_r($payload);
            $body = $payload->getBody()->getData();

            if (!$body) {
                $body = findBody($payload->getParts());
                // print_r(decodeBody($body));
            }

            // if (!$body) {
            //     $part = $payload->getParts()[0]->getParts()[0]->getBody()->getData();
            //     print_r(decodeBody($part));
            // } else {
            //     print_r(decodeBody($body));
            // }
            // $correctPart = $payload->getParts()[0]->getParts();
            // print_r(decodeBody($correctPart[0]['body']->getData()));

            $body = $payload->getBody();
            $headers = $payload->getHeaders();
            

            // print_r($body['data']);

            $FOUND_BODY = decodeBody($body['data']);
            $subject = array_values(array_filter($headers, function($k) {
                return $k['name'] == 'Subject';
            }));
            $toAddresses = array_values(array_filter($headers, function($k) {
                return $k['name'] == "To";
            }));
            $fromAddresses = array_values(array_filter($headers, function($k) {
                return $k['name'] == "From";
            }));
            $CcAddresses = array_values(array_filter($headers, function($k) {
                return $k['name'] = 'Cc';
            }));
            print_r($subject[0]->getValue());
            
            $toAddress = $toAddresses[0]->getValue();
            $date = $single_message->getInternalDate();

            if (!$FOUND_BODY) {
                $parts = $payload->getParts();
                foreach ($parts as $part) {
                    if ($part['body']) {
                        $FOUND_BODY = decodeBody($part['body']->data);
                        break;
                    }

                    if ($part['parts'] && !$FOUND_BODY) {
                        foreach ($part['parts'] as $p) {
                            if ($p['mimeType'] == 'text/html' && $p['body']) {
                                $FOUND_BODY = decodeBody($p['body']->data);
                                break;
                            }
                        }
                    }

                    if ($FOUND_BODY) {
                        break;
                    }
                }
                // print_r($FOUND_BODY->toSimpleObject());
            }
            $fromAddress = $fromAddresses[0]->getValue();
            $adpos1 = strpos($fromAddress, "<")+1;
            $adpos2 = strpos($fromAddress, ">");
            $adLength = abs($adpos1 - $adpos2);
            $fromAddress = substr($fromAddress, $adpos1, $adLength);
            // print_r("FROM ADDRESS\n\n\n$fromAddress\n\n\nEND FROM ADDRESS");
            foreach ($allowedDomains as $allowedDomain) {
                if (strpos($fromAddress, $allowedDomain) !== false) {
                    $toAddress = $toAddresses[0]->getValue();
                    if (strpos($toAddress, "+") !== false) {
                        // Formatting address address to get task_id
                        $pos1 = strpos($toAddress, "+")+1;
                        $pos2 = strpos($toAddress, "@");
                        $length = abs($pos1 - $pos2);
                        $task_id = substr($toAddress, $pos1, $length);
                        
                        // Task log creation
                        // $log = new CTaskLog();
                        // $log->task_log_task = intval($task_id);
                        // $log->task_log_name = $subject[0]->getValue();
                        // $log->task_log_description= $FOUND_BODY;
                        // $log->task_log_creator = 285;
                        // $log->task_log_hours = 1;
                        // $log->task_log_costcode = 1;
                        // $log->store();
                        // print_r($fromAddress . "\n");
                        // print_r($FOUND_BODY . "\n\n");
        
                        $mods = new Google_Service_Gmail_ModifyMessageRequest();
                        $mods->setAddLabelIds("READ");
                        $service->users_messages->modify($user, $message_id, $mods);
                    }
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
