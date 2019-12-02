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
    private $projectAddress = 'project@debortoli.com.au';

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

    /**
     * Decodes gmail message body data
     * @param string $data Data from gmail message body
     * @return string
     */
    protected function decodeBody($data) {
        $data = base64_decode(str_replace(array('-', '_'), array('+', '/'), $data));
        return $data;
    }

    /**
     * Get tags from list of email addresses
     * @param array $addresses Email addresses to check
     * @return array
     */
    protected function getAddressTags($addresses) {
        $tags = [];
        foreach ($addresses as $address) {
            if (strpos($address, '<') !== false) {
                $pos1 = strpos($address, "<")+1;
                $pos2 = strpos($address, ">");
                $length = abs($pos1 - $pos2);
                $address = substr($address, $pos1, $length);
            }
            if (strpos($address, '+') !== false) {
                $pos1 = strpos($address, '+')+1;
                $pos2 = strpos($address, '@');
                $length = abs($pos1 - $pos2);
                $tag = substr($address, $pos1, $length);
                $currentAddress = str_replace('+'.$tag, '', $address);
                if ($currentAddress == $this->projectAddress)
                    array_push($tags, $tag);
            }
        }
        return $tags;
    }

    /**
     * Gets specified var in given headers
     * @param string $name Name of header var to get
     * @param array $headers Email headers
     * @return string
     */
    protected function getHeaderVar($name, $headers) {
        foreach ($headers as $header) {
            if ($header->getName() == $name)
                return $header->getValue();
        }
    }

    /**
     * Remove arrow greater/less than symbols from email addresses
     * @param string $address Email address to check
     * @return string
     */
    protected function removeTags($address) {
        if (strpos($address, '<') !== false) {
            $pos1 = strpos($address, '<')+1;
            $pos2 = strpos($address, '>');
            $length = abs($pos1 - $pos2);
            return substr($address, $pos1, $length);
        } else
            return $address;
    }

    /**
     * Recursively check message parts to find the body text of the email
     * @param array $parts Parts of the email
     * @param string $type Which type of data to return
     * @return string
     */
    protected function findBody($parts, $type='text/html') {
        foreach ($parts as $part) {
            if ($part['mimeType'] == $type)
                return $part->getBody()->getData();
            else {
                if ($part->getParts()) {
                    $recurse = $this->findBody($part->getParts(), $type);
                    if (gettype($recurse) != 'array')
                        return $recurse;
                }
            }
        }
    }

    /**
     * If an error occurs when creating task log, send email to sender
     * @param string $from Sender email address
     * @param string $to Recipient email address
     * @param string $taskId Task id that user sent
     * @param string $subject Subject from original email
     */
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

    /**
     * Creates dotproject task log from email contents
     * @param string $taskId Task id sent in email
     * @param string $body Body of the email for task log desc
     * @param array $headers Google message headers
     * @param string $date epoch timestamp that message was send
     * @return boolean
     */
    protected function createTaskLog($taskId, $body, $headers, $date) {
        // Get owner of task using the task id
        $q = new DBQuery;
        $q->addTable("tasks");
        $q->addQuery("task_owner");
        $q->addWhere("task_id = " . $taskId);
        $taskOwner = $q->loadResult();
        $q->clear();
        // If the query cannot find the task owner, the task must not exist
        if ($taskOwner == null) {
            return false;
        }
        
        // Getting needed data from the message headers
        $subject = array_values(array_filter($headers, function($k) {
            return $k['name'] == 'Subject';
        }))[0]->getValue();
        $fromAddresses = array_values(array_filter($headers, function($k) {
            return $k['name'] == 'From';
        }));
        $fromAddrValues = [];
        foreach ($fromAddresses as $fromAddress) {
            $pos1 = strpos($fromAddress->getValue(), "<")+1;
            $pos2 = strpos($fromAddress->getValue(), ">");
            $length = abs($pos1 - $pos2);
            array_push($fromAddrValues, substr($fromAddress->getValue(), $pos1, $length));
        }
        $toAddresses = array_values(array_filter($headers, function($k) {
            return $k['name'] == 'To';
        }));
        $toAddrValues = [];
        foreach ($toAddresses as $toAddress) {
            array_push($toAddrValues, $toAddress->getValue());
        }
        $CcAddresses = explode(', ', $this->getHeaderVar('Cc', $headers));
        $BccAddresses = explode(', ', $this->getHeaderVar('Bcc', $headers));
        $CcAddr = [];
        $BccAddr = [];
        $ToAddr = [];
        foreach ($CcAddresses as $CcAddress) {
            array_push($CcAddr, $this->removeTags($CcAddress));
        }
        foreach ($BccAddresses as $BccAddress) {
            array_push($BccAddr, $this->removeTags($BccAddress));
        }
        foreach ($toAddrValues as $toAddr) {
            array_push($ToAddr, $this->removeTags($toAddr));
        }
        $newDate = date('Y-m-d H:i', substr($date, 0, 10));
        // Creating the formatted task log description
        $logBody = '<i>Automated log from email.</i><br>
                    <b>Subject:</b> ' . $subject . '<br>
                    <b>From:</b> ' . implode(', ', $fromAddrValues) . '<br>
                    <b>To:</b> ' . implode(', ', $ToAddr) . '<br>
                    <b>Cc:</b> ' . implode(', ', $CcAddr) . '<br>
                    <b>Bcc:</b> ' . implode(', ', $BccAddr) . '<br>
                    <b>Date:</b> ' . $newDate . '<br>
                    <b>Message:</b> ' . $body;
        // Create and store the new task log
        $log = new CTaskLog();
        $log->task_log_task = intval($taskId);
        $log->task_log_name = $subject;
        $log->task_log_description = $logBody;
        $log->task_log_creator = intval($taskOwner);
        $log->task_log_hours = 1;
        $log->task_log_costcode = 1;
        $log->store();
        return true;
    }


    /**
     * Retrieve next messages of a certain amount to be processed from inbox
     * @param Google_Service_Gmail $service Verified Google service instance
     * @param int $amount Amount of emails to retrieve
     * @return array
     */
    protected function getNextMessages($service, $amount) {
        $emails = array();
        $labels = ['Label_4983233390187973438', 'UNREAD'];
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
                    $CCAddresses = explode(', ', $this->getHeaderVar('Cc', $headers));

                    // Set each email to read after checking
                    $mods = new Google_Service_Gmail_ModifyMessageRequest();
                    $mods->setRemoveLabelIds(array("UNREAD"));
                    $service->users_messages->modify($this->user, $message->id, $mods);
                    
                    // Verify someone from allowed domains sent or was CC'd in the email
                    foreach($this->allowedDomains as $allowedDomain) {
                        if (strpos($fromAddress, $allowedDomain) !== false)
                            array_push($emails, $singleMessage);
                        else {
                            foreach ($CCAddresses as $CCAddress) {
                                if (strpos($CCAddress, $allowedDomain) !== false) {
                                    array_push($emails, $singleMessage);
                                    break;
                                }
                            }
                        } 
                    }
                }
                // Check for next page of emails
                if ($results->getNextPageToken() != null) {
                    $pageToken = $results->getNextPageToken();
                    $results = $service->users_messages->listUsersMessages($this->user, ['pageToken' => $pageToken, 'maxResults' => 1000, 'labelIds' => $labels]);
                } else
                    break;
            }
            return $emails;
        } catch (Google_Service_Exception $e) {
            // If no emails are found that need to be processed
            return $e->getMessage();
        }
    }


    /**
     * Processes emails in inbox
     * @param Google_Service_Gmail $service Validated Google service
     * @param int $amount Amount of emails to process 
     */
    function processMessage($service, $amount) {
        // Get next message in queue
        $nextMessages = $this->getNextMessages($service, $amount);
        if (!is_array($nextMessages))
            return;
        foreach ($nextMessages as $nextMessage) {
            $messageId = $nextMessage->id;
            $payload = $nextMessage->getPayload();
            $messageBody = $payload->getBody();
            $messageHeaders = $payload->getHeaders();

            $date = $nextMessage->getInternalDate();
            
            $subject = array_values(array_filter($messageHeaders, function($k) {
                return $k['name'] == 'Subject';
            }));
            $toAddresses = explode(', ', $this->getHeaderVar('To', $messageHeaders));
            $CcAddresses = explode(', ', $this->getHeaderVar('Cc', $messageHeaders));
            $BccAddresses = explode(', ', $this->getHeaderVar('Bcc', $messageHeaders));

            // Getting body of email
            $body = $messageBody->getData();
            if (!$body)
                $body = $this->findBody($payload->getParts(), 'text/html');
            $body = $this->decodeBody($body);
            // Checking if email address contains a task id
            $tags = [];
            if ($this->getAddressTags($toAddresses) != null)
                array_push($tags, ...$this->getAddressTags($toAddresses));
            if ($this->getAddressTags($CcAddresses) != null)
                array_push($tags, ...$this->getAddressTags($CcAddresses));
            if ($this->getAddressTags($BccAddresses) != null)
                array_push($tags, ...$this->getAddressTags($BccAddresses));
            foreach ($tags as $tag) {
                $taskLog = $this->createTaskLog($tag, $body, $messageHeaders, $date);
                if ($taskLog == false) {
                    $fromAddress = array_values(array_filter($messageHeaders, function($k) {
                        return $k['name'] == "From";
                    }))[0]->getValue();
                    return $this->sendErrorReply($toAddress, $fromAddress, $taskId, $subject[0]->getValue());
                }
            } 
        }
    }
}
