<?php

namespace MangoFp\UseCases;

use MangoFp\Entities\HistoryItem;
use MangoFp\Entities\Label;
use MangoFp\Entities\Message;
use MangoFp\Entities\Option;

class MessageUseCase {
    private $blacklistedAttributes = [
        '_wpcf7_version',
        '_wpcf7_locale',
        '_wpcf7_unit_tag',
        '_wpcf7_container_post',
        '_wpcf7cf_hidden_group_fields',
        '_wpcf7cf_hidden_groups',
        '_wpcf7cf_visible_groups',
        '_wpcf7cf_repeaters',
        '_wpcf7cf_steps',
        '_wpcf7cf_options',
        'vormiurl',
        'acceptance-824',
        'acceptance-383',
        'acceptance-231',
        'acceptance-689',
    ];

    public function __construct(iOutput $output, iStorage $storage) {
        $this->output = $output;
        $this->storage = $storage;
    }

    public function fetchExistingOrCreateNewLabelByName(string $labelName) {
        $label = $this->storage->fetchLabelByName($labelName);
        if ($label) {
            return $label;
        }

        $newLabel = (new Label())->setDataFromArray(['labelName' => $labelName]);
        $result = $this->storage->insertLabel($newLabel);
        if (!$result) {
            return null;
        }

        return $newLabel;
    }

    public function getParsingOptions() {
        $settingsUC = new SettingsUseCase(
            $this->output,
            $this->storage
        );

        return [
            'label' => $settingsUC->getOptionObj(Option::OPTION_LABEL_FIELD)->get('value'),
            'email' => $settingsUC->getOptionObj(Option::OPTION_EMAIL_FIELD)->get('value'),
            'name' => 'your-name',
            'form' => '_wpcf7',
        ];
    }

    public function parseContentAndInsertToDatabase(array $content, array $meta = []) {
        $data = [];
        $secondaries = [];
        $label = null;

        $optionsData = $this->getParsingOptions();
        $labelTag = $optionsData['label'];
        $metaLabel = $this->getShortCodeName($labelTag);

        if ('pageTitle' == $metaLabel) {
            $labelValue = $this->storage->getDefaultLabel($meta);
            $label = $this->fetchExistingOrCreateNewLabelByName($labelValue);
        } elseif (isset($meta[$metaLabel])) {
            $labelValue = $meta[$metaLabel];
            $label = $this->fetchExistingOrCreateNewLabelByName($labelValue);
        }

        $primaries = \array_flip($optionsData);
        foreach ($content as $key => $value) {
            if ($key === $labelTag) {
                $label = $this->fetchExistingOrCreateNewLabelByName($value);

                continue;
            }

            if (
                \in_array($key, $this->blacklistedAttributes)
                || !$value
                ) {
                continue;
            }

            if (isset($primaries[$key])) {
                $primaryKey = $primaries[$key];
                $data[$primaryKey] = $value;
            } else {
                $secondaries[$key] = $value;
            }
        }
        $data['content'] = \json_encode($secondaries);
        $data['raw_data'] = \json_encode($content);
        $data['labelId'] = $label ? $label->get('id') : '';
        $message = (new Message())->setDataFromArray($data);

        $res = $this->storage->insertMessage($message);
        if (!$res) {
            return $this->output->outputError('ERROR: unable to insert message', iOutput::ERROR_FAILED);
        }
        //TODO: store label and fetch labelId, send it back
        //TODO: Fetch state for code and send it back

        return $this->output->outputResult($this->makeOneMessageOutputData($message));
    }

    public function fetchAllMessagesToOutput() {
        $retData = $this->storage->fetchMessages();

        if (!\is_array($retData)) {
            return $this->output->outputError(
                'No valid result received',
                iOutput::ERROR_FAILED
            );

        }
        $messages = isset($retData['messages']) ? $retData['messages'] : false;
        $errors = isset($retData['errors']) ? $retData['errors'] : [];

        if (!\is_array($messages)) {
            $errors = !is_array($errors) ? [$errors] : $errors;

            return $this->output->outputError(
                implode(", ", $errors),
                iOutput::ERROR_FAILED
            );
        }

        $data = [];
        foreach ($messages as $message) {
            $data[] = $this->makeMessageOutputData($message);
        }

        return $this->output->outputResult([
                'messages' => $data,
                'errors' => $errors,
            ]
        );
    }

    public function updateMessageAndReturnChangedMessage($params) {
        $UPDATEABLE_FIELDS = [
            'labelId' => 'labelId',
            'email' => 'email',
            'code' => 'statusCode',
            'name' => 'name',
            'note' => 'note',
        ];

        if (!isset($params['uuid'])) {
            return $this->output->outputError('No message id in message update request', iOutput::ERROR_VALIDATION);
        }

        if (!isset($params['message'])) {
            return $this->output->outputError('No data to be updated in the request', iOutput::ERROR_VALIDATION);
        }

        $messageObj = $this->storage->fetchMessage($params['uuid']);
        if (!$messageObj) {
            return $this->output->outputError('Message not found', iOutput::ERROR_NOTFOUND);
        }

        $messageData = $messageObj->getDataAsArray();
        $paramsMessage = $params['message'];
        $updatesHistory = [];
        foreach ($UPDATEABLE_FIELDS as $key => $field) {
            if (isset($paramsMessage[$key])) {
                $updatesHistory[] = (new HistoryItem())->setMessageChanges(
                    $messageObj->get('id'), // item id
                    'admin', //account
                    $key, //change type
                    $messageData[$field], // original content
                    $paramsMessage[$key] // content
                );

                $messageData[$field] = $paramsMessage[$key];
            }
        }

        $messageObj->setDataFromArray($messageData);
        \error_log('Will update message: '.print_r($messageObj->getDataAsArray(), 1));

        $updatedMessage = $this->storage->storeMessage($messageObj);
        if (!$updatedMessage) {
            return $this->output->outputError('Message update failed', iOutput::ERROR_FAILED);
        }

        foreach ($updatesHistory as $item) {
            //TODO - add error  handling???
            $this->storage->insertHistoryItem($item);
        }

        return $this->output->outputResult($this->makeOneMessageOutputData($updatedMessage));
    }

    public function getMessageDetailsAndReturn($params) {
        \error_log(print_r($params, 1));
        $messageObj = $this->storage->fetchMessage($params['uuid']);
        if (!$messageObj) {
            return $this->output->outputError('Message not found', iOutput::ERROR_NOTFOUND);
        }

        return $this->output->outputResult($this->makeOneMessageOutputData($messageObj));
    }

    public function setHistoryItemReadIndAndReturnResult($historyItemId, $isUnread) {
        $historyItem = $this->storage->fetchHistoryItemById($historyItemId);
        if (!$historyItem) {
            return $this->output->outputError('History item with this id is not found', iOutput::ERROR_FAILED);
        }

        error_log('created history item');
        error_log(print_r($historyItem,1));

        $historyItem->setUnread($isUnread ? true : false);
        try {
            $this->storage->storeHistoryItemIsUnread($historyItem);
            return $this->output->outputResult(['updated' => true]);
        } catch(\Exception $err) {
            return $this->output->outputError(
                'Error updating unread for message history item: '.$err->getMessage(),
                iOutput::ERROR_FAILED
            );
        }
    }

    public function sendEmailAndReturnMessage($emailData, $id) {
        if (
           !isset($emailData['content'])
           || !isset($emailData['addresses'])
           || !isset($emailData['subject'])
        ) {
            \error_log('Unable to send email - email field(s) missing. Submitted: '.\wp_json_encode($emailData));

            return $this->output->outputError('Unable to send email - email field(s) missing', iOutput::ERROR_FAILED);
        }

        $messageObj = $this->storage->fetchMessage($id);
        if (!$messageObj) {
            return $this->output->outputError('Message not found', iOutput::ERROR_NOTFOUND);
        }

        $isSuccess = $this->submitEmail($emailData, $id);
        if (!$isSuccess) {
            \error_log('Unable to send email. Submitted: '.\wp_json_encode($emailData));

            return $this->output->outputError('Sending email failed', iOutput::ERROR_FAILED);
        }

        return $this->output->outputResult($this->makeOneMessageOutputData($messageObj));
    }

    public function sendEmailAndUpdateMessageAndReturnChangedMessage($emailData, $params) {
        if (
           !isset($emailData['content'])
           || !isset($emailData['addresses'])
           || !isset($emailData['subject'])
        ) {
            \error_log('Unable to send email - email field(s) missing. Submitted: '.\wp_json_encode($emailData));

            return $this->output->outputError('Unable to send email - email field(s) missing', iOutput::ERROR_FAILED);
        }
        $code = isset($params['message']['code']) ? $params['message']['code'] : 'none';
        $isSuccess = $this->submitEmail($emailData, $params['uuid'], $code);
        if (!$isSuccess) {
            \error_log('Unable to send email. Submitted: '.\wp_json_encode($emailData));

            return $this->output->outputError('Sending email failed', iOutput::ERROR_FAILED);
        }

        return $this->updateMessageAndReturnChangedMessage($params);
    }

    protected function makeOneMessageOutputData(Message $message) {
        return [
            'message' => $this->makeMessageOutputData($message),
        ];
    }

    protected function makeMessageOutputData(Message $message) {
        $historyList = $this->storage->fetchItemHistory($message->get('id'));

        $isUnread = false;
        foreach ($historyList as $hItem) {
            if ($hItem['isUnread']) {
                $isUnread = true;
                break;
            }
        }
        //Lisa isUnread = true kui mõni history item  isUnread
        return [
            'id' => $message->get('id'),
            'form' => $message->get('form'),
            'code' => $message->get('statusCode'),
            'content' => $message->get('content'),
            'labelId' => $message->get('labelId'),
            'email' => $message->get('email'),
            'name' => $message->get('name'),
            'note' => $message->get('note'),
            'isUnread' => $isUnread,
            'lastUpdated' => $message->lastUpdated(),
            'changeHistory' => $historyList,
        ];
    }

    protected function getReplyAddressAsEmailHeader($id) {
        $settingsUC = new SettingsUseCase(
            $this->output,
            $this->storage
        );
        $optionReplyEmail = $settingsUC->getOptionObj(Option::OPTION_REPLY_EMAIL);
        $optionReplyEmailName = $settingsUC->getOptionObj(Option::OPTION_REPLY_EMAIL_NAME);
        $replyToHeader = sprintf(
            '%s <%s>',
            $optionReplyEmailName->get('value'),
            $optionReplyEmail->get('value')
        );
        return \apply_filters('mangofp_emails_update', [
                'contactId' => $id,
                'replyToHeader' => $replyToHeader
            ]
        );
    }

    protected function getFromAddressAsEmailHeader() {
        $settingsUC = new SettingsUseCase(
            $this->output,
            $this->storage
        );
        $optionReplyEmail = $settingsUC->getOptionObj(Option::OPTION_REPLY_EMAIL);
        $optionReplyEmailName = $settingsUC->getOptionObj(Option::OPTION_REPLY_EMAIL_NAME);

        //TODO: filter premiumi jaoks
        return sprintf(
            'From: %s <%s>',
            $optionReplyEmailName->get('value'),
            $optionReplyEmail->get('value')
        );
    }

    protected function submitEmail($emailData, $id, $code = 'none') {
        $to = $emailData['addresses'];
        $subject = $emailData['subject'];
        $body = $emailData['content'];
        $attachments = [];
        $urls = [];
        if (isset($emailData['attachments'])) {
            foreach ($emailData['attachments'] as $attachmentId) {
                $url = \wp_get_attachment_url($attachmentId);
                $filePath = \get_attached_file($attachmentId);
                if (!$filePath) {
                    $email = $emailData['email'];
                    error_log("No file found for attachment {$attachmentId} while attempting to send email to {$email}");

                    return false;
                }
                $attachments[] = $filePath;
                $urls[] = $url;
            }
        }
        $success = false;

        //TODO: refactor email sending to the Adapter level
        //do not send email from development environment

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $headers[] = $this->getFromAddressAsEmailHeader($id);
        //$headers[] = 'From: ' . $this->getReplyAddressAsEmailHeader($id);
        $headers[] = 'Reply-To: ' . $this->getReplyAddressAsEmailHeader($id);
        $ccForHistory = '';
        if (isset($emailData['ccAddresses']) && is_array($emailData['ccAddresses'])) {
            foreach ($emailData['ccAddresses'] as $email) {
                $headers[] = 'Cc: '.$email;
            }
            $ccForHistory = implode(', ', $emailData['ccAddresses']);
        }

        if (defined('MANGO_FP_DEBUG') && MANGO_FP_DEBUG) {
            $success = true;
            error_log(print_r($headers, true));
        } else {
            error_log('Headers for sending email:');
            error_log(print_r($headers, true));
            $success = wp_mail(
                $to,
                $subject,
                $body,
                $headers, //TODO - add header to set reply address for incoming emails. e.g:  'Reply-To: Person Name <person.name@example.com>',
                $attachments
            );
        }

        if ($success) {
            $historyItem = (new HistoryItem())->setEmailSent(
                $id, // item id
                'admin', //account
                $code, //change type
                [ // emailData
                    'to' => $to,
                    'cc' => $ccForHistory,
                    'subject' => $subject,
                    'message' => $body."\r\n\r\n"."Attachments:\r\n".implode(
                        "\r\n",
                        $urls
                    ),
                    'attachments' => json_encode($attachments),
                ]
            );
            $this->storage->insertHistoryItem($historyItem);
        }

        return $success;
    }

    private function getShortCodeName($shortCode) {
        if (
            '[' == $shortCode[0]
            && ']' == $shortCode[\strlen($shortCode) - 1]
        ) {
            return \substr($shortCode, 1, \strlen($shortCode) - 2);
        }

        return false;
    }
}
