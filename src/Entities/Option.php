<?php

namespace MangoFp\Entities;

class Option extends BaseEntity {
    const OPTION_STEPS = 'steps';
    const OPTION_COMMENT = 'comment';
    const OPTION_REPLY_EMAIL = 'reply_email';
    const OPTION_EMAIL_FIELD = 'email_field';
    const OPTION_LABEL_FIELD = 'label_field';
    const OPTION_REPLY_EMAIL_NAME = 'reply_email_name';

    const OPTION_TYPE_EMAIL = 'EMAIL';
    const OPTION_TYPE_TEXT = 'TEXT';

    public function __construct($data = []) {
        parent::__construct();
        $this->data = \array_merge($this->data, [
            'key' => $data['key'] ?? '',
            'value' => $data['value'] ?? '',
            'modify_time' => $data['modify_time'] ?? '',
        ]);
        $this->className = 'Option';
    }

    public static function getAllOptionsDefinitions() {
        $rowElems = [
            sprintf('<li><strong>[pageTitle]</strong> - %s</li>', __("name of the page where submitted form is located")),
            sprintf('<li><strong>[pageId]</strong> - %s</li>', __("id of the page where submitted form is located")),
            sprintf('<li><strong>[formName]</strong> - %s</li>', __("name of the submitted form")),
            sprintf('<li><strong>[formId]</strong> - %s</li>', __("id of the submitted form")),
        ];

        $labelHint = sprintf("%s<p>%s</p><ul>%s</ul>",
            __('Name of the Contact Form field that will be used as label for contact records in Mango Contacts.'),
            __('Use form field name or following shortcodes for dynamic values:'),
            implode('', $rowElems)
        );

        $allOptions = [
            Option::OPTION_EMAIL_FIELD => [
            "label" => Option::OPTION_EMAIL_FIELD,
            "type" => Option::OPTION_TYPE_TEXT,
            "name" => __("Email field"),
            "hint" => __(
                "Name of the Email field on Contact Form(s) that will be used as contact's email for contact records in Mango Contacts. Default: email",
                ),
            ],
            Option::OPTION_LABEL_FIELD => [
                "label" => Option::OPTION_LABEL_FIELD,
                "type" => Option::OPTION_TYPE_TEXT,
                "name" => __("Label field"),
                "hint" => $labelHint,
            ],
            Option::OPTION_REPLY_EMAIL => [
                "label" => Option::OPTION_REPLY_EMAIL,
                "type" => Option::OPTION_TYPE_EMAIL,
                "name" => __("From email"),
                "hint" => __(
                    "Email address of the sender. Used also for replies to emails sent from MangoFp. Premium emails add-on plugin enables receiving and management of replies directly in MangoFp",
                ),
            ],
            Option::OPTION_REPLY_EMAIL_NAME => [
                "label" => Option::OPTION_REPLY_EMAIL_NAME,
                "type" => Option::OPTION_TYPE_TEXT,
                "name" => __("Email sender name"),
                "hint" => __(
                    "Email sender's name for emails sent from MangoFp.",
                ),
            ],
        ];

        error_log('Getting options ...');

        $allOptions = apply_filters('mangofp_emails_options_defs', $allOptions);
        return $allOptions;
    }

    public static function getListOfAllOptions() {
        $allowedOptions = array_keys(Option::getAllOptionsDefinitions());
        $allowedOptions[] = Option::OPTION_STEPS;
        return $allowedOptions;
    }

    public static function isValidOption(string $key) {
        if (!\in_array($key, Option::getListOfAllOptions())) {
            return false;
        }

        return true;
    }
}
