<?php
namespace MangoFp\Entities;

const HISTORY_ITEM_MSGCREATE = 'MSG_CREATED';
const HISTORY_ITEM_LBLCREATE = 'LBL_CREATED';
const HISTORY_ITEM_MSGCHANGE = 'MSG_CHANGED';
const HISTORY_ITEM_EMAILSENT = 'EMAIL_SENT';
const HISTORY_ITEM_EMAILRECEIVED = 'EMAIL_RECEIVED';

const HISTORY_ITEM_MSGCHANGE_STATUS = 'STATUS_CODE';
const HISTORY_ITEM_MSGCHANGE_LABELID = 'LABEL_ID';
const HISTORY_ITEM_MSGCHANGE_EMAIL = 'EMAIL';
const HISTORY_ITEM_MSGCHANGE_CONTENT = 'CONTENT';

class HistoryItem extends BaseEntity {
    function __construct($data = []) {
        parent::__construct();
        $this->className = "HistoryItem";
        $this->data['isUnread'] = 0;
        $this->data['userAccount'] = "";
        if ($data) {
            if (isset($data['id'])) {
                $this->data['id'] = $data['id'];
            }

            $this->data = \array_merge( $this->data, [
                'itemId' => $data['itemId'],
                'create_time' => $data['create_time'],
                'changeType' => $data['changeType'],
                'changeSubType' => $data['changeSubType'] ?? '',
                'originalContent' => $data['originalContent'] ?? '',
                'content' => $data['content'],
                'userAccount' => $data['userAccount'],
                'isUnread' => (isset($data['isUnread']) && $data['isUnread']) ? true : false
            ]);
        }

        $this->emailId = "";
    }

    function  setDataFromArray($newData, $loading = false) {
        throw new Exception("History items should not be changed after creation (except for read/unread setting)", 1);
    }

    function setUnread($isUnread) {
        $this->data['isUnread'] = $isUnread;
        return $this;
    }

    function setCreateMessage(string $itemId, string $account, string $subtype, array $content) {
        $this->data = \array_merge( $this->data, [
            'itemId' => $itemId,
            'changeType' => HISTORY_ITEM_MSGCREATE,
            'changeSubType' => $subtype ? $subtype : '',
            'originalContent' => '',
            'content' => \json_encode($content),
            'userAccount' => $account
        ]);

        return $this;
    }

    function setCreateLabel(string $itemId, string $account, string $subtype, array $content) {
        $this->data = \array_merge( $this->data, [
            'itemId' => $itemId,
            'changeType' => HISTORY_ITEM_LBLCREATE,
            'changeSubType' => $subtype ? $subtype : '',
            'originalContent' => '',
            'content' => \json_encode($content),
            'userAccount' => $account
        ]);

        return $this;
    }

    function setMessageChanges(
        string $itemId,
        string $account,
        string $subtype,
        string $originalContent,
        string $content
    ) {
        if (!$subtype) {
            throw new Exception("Subtype missing for message change history record", 1);
        }

        $this->data = \array_merge( $this->data, [
            'itemId' => $itemId,
            'changeType' => HISTORY_ITEM_MSGCHANGE,
            'changeSubType' => $subtype,
            'originalContent' => $originalContent,
            'content' => $content,
            'userAccount' => $account
        ]);

        return $this;
    }

    function setEmailSent(
        string $itemId,
        string $account,
        string $subtype,
        array $emailData
    ) {
        if (!$subtype) {
            throw new \Exception("Subtype missing for email sent history record", 1);
        }

        $this->data = \array_merge( $this->data, [
            'itemId' => $itemId,
            'changeType' => HISTORY_ITEM_EMAILSENT,
            'changeSubType' => $subtype,
            'originalContent' => '',
            'content' =>  \json_encode($emailData),
            'userAccount' => $account
        ]);

        return $this;
    }

    //Email Id of new - just downloaded email. Not stored in database as data 
    function setEmailId($emailId) {
        $this->emailId = $emailId;
    }

    function getEmailId() {
        return $this->emailId;
    }

}