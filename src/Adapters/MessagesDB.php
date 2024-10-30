<?php

namespace MangoFp;

use MangoFp\Entities\HistoryItem;
use MangoFp\Entities\Label;
use MangoFp\Entities\Message;
use MangoFp\Entities\Option;
use MangoFp\Entities\Template;
use MangoFp\UseCases\iStorage;

class MessagesDB implements iStorage {
    const VERSION_PARAM_NAME = 'mangofp_db_version';
    const TABLE_MESSAGES = 'mangofp_messages';
    const TABLE_LABELS = 'mangofp_labels';
    const TABLE_HISTORY = 'mangofp_history';
    const TABLE_OPTIONS = 'mangofp_options';
    const TABLE_TEMPLATES = 'mangofp_templates';

    public static function installOrUpdateDatabase() {
        global $wpdb;
        $mangoVersion = getVersion();
        $actualVersion = get_site_option($wpdb->prefix.self::VERSION_PARAM_NAME, '0.0.0');
        if ($mangoVersion == $actualVersion) {
            return;
        }
        error_log(
            sprintf(
                'installing database version %s -> %s',
                $actualVersion,
                $mangoVersion
            )
        );

        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $table_messages = $wpdb->prefix.self::TABLE_MESSAGES;
        $table_labels = $wpdb->prefix.self::TABLE_LABELS;
        $table_history = $wpdb->prefix.self::TABLE_HISTORY;
        $table_options = $wpdb->prefix.self::TABLE_OPTIONS;
        $table_templates = $wpdb->prefix.self::TABLE_TEMPLATES;
        $createSql = "CREATE TABLE {$table_labels} (
            id varchar(100) NOT NULL,
            create_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            modify_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            delete_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            label_name varchar(100),
            UNIQUE KEY id (id),
            KEY label_name (label_name)
        ) {$charset_collate};";
        dbDelta($createSql);

        $createSql = "CREATE TABLE {$table_messages} (
            id varchar(100) NOT NULL,
            create_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            modify_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            delete_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            label_id varchar(100),
            status_code varchar(100),
            email varchar(100),
            person_name varchar(100),
            note varchar(4000),
            content varchar(4000),
            rawdata varchar(4000),
            is_unread tinyint(1) DEFAULT 0,
            UNIQUE KEY id (id)
        ) {$charset_collate};";
        dbDelta($createSql);

        $createSql = "CREATE TABLE {$table_history} (
            id varchar(100) NOT NULL,
            create_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            item_id varchar(100),
            change_type varchar(30),
            change_subtype varchar(30),
            original_content varchar(4000),
            user_account varchar(100),
            content varchar(4000),
            is_unread tinyint(1) DEFAULT 0,
            UNIQUE KEY id (id),
            KEY user_account (user_account)
        ) {$charset_collate};";
        dbDelta($createSql);

        $createSql = "CREATE TABLE {$table_options} (
            create_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            modify_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            delete_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            option_key varchar(100),
            option_value text,
            UNIQUE KEY option_key (option_key)
        ) {$charset_collate};";
        dbDelta($createSql);

        $createSql = "CREATE TABLE {$table_templates} (
            create_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            modify_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            delete_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            code varchar(100),
            template text,
            main_addresses text,
            cc_addresses varchar(4000),
            UNIQUE KEY code (code)
        ) {$charset_collate};";
        dbDelta($createSql);

        update_option($wpdb->prefix.self::VERSION_PARAM_NAME, $mangoVersion);
    }

    public static function removeDatabase() {
        global $wpdb;

        if (keepDbOnUninstall()) {
            return;
        }

        $table_name = $wpdb->prefix.self::TABLE_MESSAGES;
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        $wpdb->query($sql);
        $table_name = $wpdb->prefix.self::TABLE_LABELS;
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        $wpdb->query($sql);
        $table_history = $wpdb->prefix.self::TABLE_HISTORY;
        $sql = "DROP TABLE IF EXISTS {$table_history}";
        $wpdb->query($sql);
        $table_name = $wpdb->prefix.self::TABLE_OPTIONS;
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        $wpdb->query($sql);
        $table_name = $wpdb->prefix.self::TABLE_TEMPLATES;
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        $wpdb->query($sql);

        delete_option($wpdb->prefix.self::VERSION_PARAM_NAME);
    }

    //TODO: remove
    public function getLabelTag() {
        return 'pealkiri';
    }

    public function getDefaultLabel($meta) {
        //Finds default value for label that is a name of the page/post of the form
        //Can be overriden by defining label field with the name of the contact form field
        if (\is_front_page() || \is_home()) {
            return \get_bloginfo('name');
        }
        if (isset($meta['pageId'])) {
            $post = get_post($meta['pageId']);
            if ($post) {
                return esc_html($post->post_title);
            }
        }

        return \wp_title('');
    }

    public function storeMessage(Message $message) {
        global $wpdb;
        if (!$message || !$message->get('id')) {
            return null;
        }
        $result = $wpdb->update(
            $wpdb->prefix.self::TABLE_MESSAGES,
            $this->parseMessageToDbData($message),
            ['id' => $message->get('id')]
        );
        if (!$result) {
            error_log('ERROR: Message Update failed with data: '.\json_encode($message->getDataAsArray()));

            return false;
        }
        if ($result > 1) {
            throw new \Error('More than one message was updated!!!!');
        }

        return $message;
    }

    public function insertMessage(Message $message) {
        error_log('Inserting message');
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix.self::TABLE_MESSAGES,
            $this->parseMessageToDbData($message)
        );
        if (1 != $result) {
            //error_log('ERROR: Message Insert failed with data: ' . \json_encode($message->getDataAsArray()));
            error_log('ERROR: Message Insert failed');

            return false;
        }

        return true;
    }

    public function messageExists(Message $message) {
        return false;
    }

    public function fetchMessage(string $id) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_MESSAGES;
        $request = $wpdb->prepare(
            "SELECT id, create_time, modify_time, delete_time, label_id, status_code, email, person_name, content, rawdata, note, is_unread
            FROM {$table_name}
            WHERE id = '%s';
            ",
            [$id]
        );
        $messageRow = $wpdb->get_row($request, ARRAY_A);
        if (!$messageRow) {
            return null;
        }

        return $this->makeMessageWithDbData($messageRow);
    }

    public function fetchMessages() {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_MESSAGES;
        $messageRows = $wpdb->get_results(
            "  SELECT id, create_time, modify_time, delete_time, label_id, status_code, email, person_name, content, rawdata, note, is_unread
                FROM {$table_name}
                ORDER BY status_code, create_time desc;
            ",
            ARRAY_A
        );

        $messagesData = [
            'messages' => $messageRows,
            'errors' => [],
        ];
        $messagesData = apply_filters('mangofp_fetch_additional_messages', $messagesData);

        $allMessages = [];
        foreach ($messagesData['messages'] as $messageRow) {
            $allMessages[] = $this->makeMessageWithDbData($messageRow);
        }

        return ['messages' => $allMessages, 'errors' => $messagesData['errors']];
    }

    public function insertLabel(Label $label) {
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix.self::TABLE_LABELS,
            [
                'id' => $label->get('id'),
                'label_name' => $label->get('labelName'),
                'modify_time' => $label->get('modify_time'),
                'create_time' => $label->get('create_time'),
            ]
        );
        if (1 != $result) {
            error_log('ERROR: Label Insert failed with data: '.\json_encode($label->getDataAsArray()));

            return false;
        }

        return true;
    }

    public function fetchLabelByName(string $labelName) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_LABELS;
        $request = $wpdb->prepare(
            "SELECT id, create_time, modify_time, delete_time, label_name
            FROM {$table_name}
            WHERE label_name LIKE '%s';
            ",
            [$labelName]
        );
        $labelRow = $wpdb->get_row($request, ARRAY_A);
        if (!$labelRow) {
            return null;
        }

        return (new Label())->setDataFromArray(
            [
                'id' => $labelRow['id'],
                'labelName' => $labelRow['label_name'],
                'create_time' => $labelRow['create_time'],
                'delete_time' => $labelRow['delete_time'],
                'modify_time' => $labelRow['modify_time'],
            ],
            true
        );
    }

    public function fetchLabels() {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_LABELS;
        $labelRows = $wpdb->get_results(
            "  SELECT id, create_time, modify_time, delete_time, label_name
                FROM {$table_name}
                ORDER BY label_name;
            ",
            ARRAY_A
        );
        if (!$labelRows) {
            return [];
        }

        $labels = [];
        foreach ($labelRows as $labelRow) {
            $labels[] = (new Label())->setDataFromArray(
                [
                    'id' => $labelRow['id'],
                    'labelName' => $labelRow['label_name'],
                    'create_time' => $labelRow['create_time'],
                    'delete_time' => $labelRow['delete_time'],
                    'modify_time' => $labelRow['modify_time'],
                ],
                true
            );
        }

        return $labels;
    }

    public function insertHistoryItem(HistoryItem $historyItem) {
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix.self::TABLE_HISTORY,
            $this->parseHistoryItemToDbData($historyItem)
        );
        if (1 != $result) {
            error_log('ERROR: History item insert failed with data: '.\json_encode($this->parseHistoryItemToDbData($historyItem)));
            error_log('Last error: '.$wpdb->last_error);

            return false;
        }

        return true;
    }

    public function fetchItemHistory(string $id) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_HISTORY;
        $request = $wpdb->prepare(
            "SELECT id, create_time, item_id, change_type, change_subtype, original_content, content, user_account, is_unread
            FROM {$table_name}
            WHERE item_id = '%s'
            ORDER BY create_time DESC;
            ",
            [$id]
        );
        $historyRows = $wpdb->get_results($request, ARRAY_A);
        if (!$historyRows) {
            return [];
        }

        $ħistory = [];
        foreach ($historyRows as $row) {
            $history[] = $this->makeHistoryItemWithDbData($row)->getDataAsArray();
        }

        return $history;
    }

    public function fetchHistoryItemById(string $historyItemId) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_HISTORY;

        $request = $wpdb->prepare(
            "SELECT
                id,
                create_time,
                item_id,
                change_type,
                change_subtype,
                original_content,
                content,
                user_account,
                is_unread
            FROM {$table_name}
            WHERE id = '%s';
            ",
            [$historyItemId]
        );

        $row = $wpdb->get_row($request, ARRAY_A);
        if (!$row) {
            return null;
        }

        return $this->makeHistoryItemWithDbData($row);
    }

    public function storeHistoryItemIsUnread(HistoryItem $historyItem) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_HISTORY;

        $result = $wpdb->update(
            $wpdb->prefix.self::TABLE_HISTORY,
            ['is_unread' => $historyItem->get('isUnread') ? 1 : 0],
            ['id' => $historyItem->get('id')]
        );
        
        if ($result > 1) {
            throw new \Error('More than one histroy item was updated!!!!');
        }
        return true;
    }

    public function storeTemplate(Template $emailTemplate) {
        global $wpdb;

        $result = $wpdb->replace(
            $wpdb->prefix.self::TABLE_TEMPLATES,
            [
                'code' => $emailTemplate->get('code'),
                'template' => $emailTemplate->get('template'),
                'cc_addresses' => json_encode($emailTemplate->get('addresses')),
                'main_addresses' => json_encode($emailTemplate->get('mainAddresses')),
                'modify_time' => $emailTemplate->get('modify_time'),
            ]
        );
        if (!$result) {
            error_log('ERROR: Tempalte item insert failed');

            return false;
        }

        return true;
    }

    public function fetchTemplates() {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_TEMPLATES;
        $templateRows = $wpdb->get_results(
            "   SELECT code, template, cc_addresses, main_addresses, modify_time
                FROM {$table_name}
            ",
            ARRAY_A
        );

        $allTemplates = [];
        foreach ($templateRows as $templateRow) {
            $templateObj = $this->makeTemplateWithDbData($templateRow);
            if ($templateObj) {
                $allTemplates[] = $templateObj;
            }
        }

        return $allTemplates;
    }

    public function fetchTemplate(string $code) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_TEMPLATES;
        $request = $wpdb->prepare(
            "SELECT code, template, cc_addresses, main_addresses, modify_time
            FROM {$table_name}
            WHERE code = '%s'
            ",
            [$code]
        );
        $templateRow = $wpdb->get_row($request, ARRAY_A);
        if (!$templateRow) {
            return false;
        }

        return $this->makeTemplateWithDbData($templateRow);
    }

    public function getAdminEmail() {
        return \get_option('admin_email');
    }

    public static function makeTemplateWithDbData($templateRow) {
        $templateObj = new Template();
        $templateObj->setDataFromArray(
            [
                'code' => $templateRow['code'],
                'template' => $templateRow['template'],
                'addresses' => $templateRow['cc_addresses'],
                'mainAddresses' => $templateRow['main_addresses'],
                'modify_time' => $templateRow['modify_time'],
            ],
            true
        );

        return $templateObj;
    }

    public static function parseMessageToDbData(Message $message) {
        return [
            'id' => $message->get('id'),
            'modify_time' => $message->get('modify_time'),
            'create_time' => $message->get('create_time'),
            'delete_time' => $message->get('delete_time'),
            'status_code' => $message->get('statusCode'),
            'label_id' => $message->get('labelId') ? $message->get('labelId') : null,
            'email' => $message->get('email'),
            'person_name' => $message->get('name'),
            'content' => $message->get('content'),
            'rawdata' => $message->get('rawData'),
            'note' => $message->get('note')
        ];
    }

    public static function parseHistoryItemToDbData(HistoryItem $historyItem) {
        return [
            'id' => $historyItem->get('id'),
            'create_time' => $historyItem->get('create_time'),
            'item_id' => $historyItem->get('itemId'),
            'change_type' => $historyItem->get('changeType'),
            'change_subtype' => $historyItem->get('changeSubType'),
            'original_content' => $historyItem->get('originalContent'),
            'content' => $historyItem->get('content'),
            'user_account' => $historyItem->get('userAccount'),
            'is_unread' => $historyItem->get('isUnread') ? 1 : 0,
        ];
    }

    public static function parseOptionToDbData(Option $optionObj) {
        $optionData = $optionObj->getDataAsArray();

        return [
            'modify_time' => $optionData['modify_time'],
            'option_key' => $optionData['key'],
            'option_value' => $optionData['value'],
        ];
    }

    public static function makeOptionWithDbData($data) {
        $optionObj = new Option();
        $optionObj->setDataFromArray(
            [
                'modify_time' => $data['modify_time'],
                'key' => $data['option_key'],
                'value' => $data['option_value'],
            ],
            true
        );

        return $optionObj;
    }

    public function storeOption(Option $optionObj) {
        global $wpdb;

        try {
            $wpdb->replace(
                $wpdb->prefix.self::TABLE_OPTIONS,
                $this->parseOptionToDbData($optionObj)
            );
        } catch (\Exception $err) {
            error_log('Unable to store option '.$optionObj->get('code'));
            error_log($err->getMessage());

            return false;
        }

        return true;
    }

    public function fetchOption(string $optionKey) {
        global $wpdb;
        $table_name = $wpdb->prefix.self::TABLE_OPTIONS;
        $request = $wpdb->prepare(
            "SELECT create_time, modify_time, option_key, option_value
            FROM {$table_name}
            WHERE option_key = '%s'
            ",
            [$optionKey]
        );

        $optionRow = $wpdb->get_row($request, ARRAY_A);
        if (!$optionRow) {
            return false;
        }

        return $this->makeOptionWithDbData($optionRow);
    }

    protected function makeMessageWithDbData($messageRow) {
        return (new Message())->setDataFromArray(
            [
                'id' => $messageRow['id'],
                'create_time' => $messageRow['create_time'],
                'delete_time' => $messageRow['delete_time'],
                'modify_time' => $messageRow['modify_time'],
                'labelId' => $messageRow['label_id'],
                'statusCode' => $messageRow['status_code'],
                'email' => $messageRow['email'],
                'name' => $messageRow['person_name'],
                'content' => $messageRow['content'],
                'rawData' => $messageRow['rawdata'],
                'note' => $messageRow['note'],
            ],
            true
        );
    }

    protected function makeHistoryItemWithDbData($data) {
        $createdItem =  new HistoryItem([
            'id' => $data['id'],
            'create_time' => $data['create_time'],
            'itemId' => $data['item_id'],
            'changeType' => $data['change_type'],
            'changeSubType' => $data['change_subtype'],
            'originalContent' => $data['original_content'],
            'content' => $data['content'],
            'userAccount' => $data['user_account'],
            'isUnread' => $data['is_unread'] ? true : false,
        ]);

        return $createdItem;
    }
}
