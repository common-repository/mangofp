<?php
namespace MangoFp;
class Localization {
    static public function getContactsStrings() {
        $MANGOFP = 'mangofp';
        return [
            'Send' => esc_html__('Send', $MANGOFP),
            'State' => esc_html__('State', $MANGOFP),
            'All' => esc_html__('All', $MANGOFP),
            'Confirm' => esc_html__('Confirm', $MANGOFP),
            'Confirm and send' => esc_html__('Confirm and send', $MANGOFP),
            'Date' => esc_html__('Date', $MANGOFP),
            'Status' => esc_html__('Status', $MANGOFP),
            'Label' => esc_html__('Label', $MANGOFP),
            'Note' => esc_html__('Note', $MANGOFP),
            'Name' => esc_html__('Name', $MANGOFP),
            'Email' => esc_html__('Email', $MANGOFP),
            'Updated' => esc_html__('Updated', $MANGOFP),
            'Change' => esc_html__('Change', $MANGOFP),
            'Subject' => esc_html__('Subject', $MANGOFP)
        ];
    }
    static public function getSettingsStrings() {
        $MANGOFP = 'mangofp';
        return [
            'Confirm' => esc_html__('Confirm', $MANGOFP),
            'Confirm and send' => esc_html__('Confirm and send', $MANGOFP),
            'MangoFp settings' => esc_html__('MangoFp settings', $MANGOFP),
            'Process' => esc_html__('Process', $MANGOFP),
            'Parameters' => esc_html__('Parameters', $MANGOFP)
        ];
    }

}