<?php

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
$AppUI = new CAppUI;
require_once($AppUI->getLibraryClass('google-api-php-client-2.2.1/vendor/autoload'));
require_once($AppUI->getModuleClass('tasks'));

class Mail2Log {
    protected $credentialsPath = '/srv/dotproject/credentials.json';
    protected $tokenPath = '/srv/dotproject/token.json';
    protected $allowedDomains = ["debortoli.com.au"];
    private $user = 'me';

    function getClient() {
        $client = new Google_Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes(Google_Service_Gmail::GMAIL_MODIFY);
        $client->setAuthConfig($this->credentialsPath);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
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
            if (!file_exists(dirname($this->tokenPath))) {
                mkdir(dirname($this->tokenPath), 0700, true);
            }
            file_put_contents($this->tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    function getService($client) {
        return new Google_Service_Gmail($client);
    }

    protected function createTaskLog($taskId, $body, $subject) {
        // Get owner of task using the task id
        $q = new DBQuery;
        $q->addTable("tasks");
        $q->addQuery("task_owner");
        $q->addWhere("task_id = " . $taskId);
        $taskOwner = $q->loadResult();
        $q->clear();
        if ($taskOwner == null) {
            return false;
        }
        
        // Create new task log
        $log = new CTaskLog();
        $log->task_log_task = intval($taskId);
        $log->task_log_name = $subject;
        $log->task_log_description = "Automated log from email.<br>" . $body;
        $log->task_log_creator = intval($taskOwner);
        $log->task_log_hours = 1;
        $log->task_log_costcode = 1;
        $log->store();
        return true;
    }

    protected function getNextMessages($service, $amount) {
        $emails = array();
        $labels = ["INBOX", "UNREAD"];
        $results = $service->users_messages->listUsersMessages($this->user, ['labelIds' => $labels]);
        $messages = array_reverse($results->getMessages());
        // Loop through all unread messages until it finds one to process
        try {
            while ($messages != null) {
                foreach ($messages as $message) {
                    if(count($emails) >= $amount) {
                        return $emails;
                    }
                    $optParamsGet2 = [];
                    $optParamsGet2['format'] = 'full';

                    $singleMessage = $service->users_messages->get($this->user, $message->id, $optParamsGet2);
                    $payload = $singleMessage->getPayLoad();
                    $headers = $payload->getHeaders();
                    $fromAddress = array_values(array_filter($headers, function($k) {
                        return $k['name'] == "From";
                    }))[0]->getValue();

                    $mods = new Google_Service_Gmail_ModifyMessageRequest();
                    $mods->setRemoveLabelIds(array("UNREAD"));
                    $service->users_messages->modify($this->user, $message->id, $mods);
                    
                    // Verify it was sent from someone within the allowed domains
                    foreach($this->allowedDomains as $allowedDomain) {
                        if (strpos($fromAddress, $allowedDomain) !== false) {
                            array_push($emails, $singleMessage);
                        }
                    }
                }
                // Check for next page of emails
                if ($results->getNextPageToken() != null) {
                    $pageToken = $results->getNextPageToken();
                    $results = $service->users_messages->listUsersMessages($this->user, ['pageToken' => $pageToken, 'maxResults' => 1000, 'labelIds' => $labels]);
                } else {
                    break;
                }
            }
            return $emails;


        } catch (Google_Service_Exception $e) {
            // If no emails are found that need to be processed
            return $e->getMessage();
        }
    }

    function processMessage($service, $amount) {
        // Get next message in queue
        $nextMessages = $this->getNextMessages($service, $amount);
        if (!is_array($nextMessages))
            return $nextMessages;
        foreach ($nextMessages as $nextMessage) {
            $messageId = $nextMessage->id;
            $payload = $nextMessage->getPayload();
            $messageBody = $payload->getBody();
            $messageHeaders = $payload->getHeaders();

            $date = $nextMessage->getInternalDate();
            $body = $this->decodeBody($messageBody['data']);
            $subject = array_values(array_filter($messageHeaders, function($k) {
                return $k['name'] == 'Subject';
            }));
            $toAddresses = array_values(array_filter($messageHeaders, function($k) {
                return $k['name'] == 'To';
            }));
            // Getting body of email
            if (!$body) {
                $parts = $payload->getParts();
                foreach ($parts as $part) {
                    if ($part['body']) {
                        $body = $this->decodeBody($part['body']->data);
                        break;
                    }
                    if ($part['parts'] && !$body) {
                        foreach ($part['parts'] as $p) {
                            if ($p['mimeType'] === 'text/html' && $p['body']) {
                                $body = decodeBody($p['body']->data);
                                break;
                            }
                        }
                    }
                    if ($body) {
                        break;
                    }
                }
            }
            // Checking if email address contains a task id
            $toAddress = $toAddresses[0]->getValue();
            if (strpos($toAddress, "+") !== false) {
                $pos1 = strpos($toAddress, "+")+1;
                $pos2 = strpos($toAddress, "@");
                $length = abs($pos1 - $pos2);
                $taskId = substr($toAddress, $pos1, $length);

                $taskLog = $this->createTaskLog($taskId, $body, $subject[0]->getValue());
                if ($taskLog == false) {
                    $fromAddress = array_values(array_filter($messageHeaders, function($k) {
                        return $k['name'] == "From";
                    }))[0]->getValue();
                    return $this->sendErrorReply($toAddress, $fromAddress, $taskId, $subject[0]->getValue());
                }
            }
        }
    }

    protected function sendErrorReply($from, $to, $taskId, $subject) {
        // Format email addresses

        $pos1 = strpos($to, "<")+1;
        $pos2 = strpos($to, ">");
        $length = abs($pos1 - $pos2);
        $toAddr = substr($to, $pos1, $length);
        //Send email
        $mail = new Mail;
        $mail->Subject("DP ERROR - " . $subject);
        $mail->Body("The task id you entered [$taskId] is not a valid task id.");
        $mail->From($from);
        $mail->To($toAddr);
        $mail->Send();
    }

    protected function decodeBody($body) {
        $rawData = $body;
        $sanitisedData = strtr($rawData, '-_', '+/');
        $decodedMessage = base64_decode($sanitisedData);
        if (!$decodedMessage) {
            $decodedMessage = FALSE;
        }
        return $decodedMessage;
    }

}