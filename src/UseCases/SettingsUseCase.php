<?php

namespace MangoFp\UseCases;

use MangoFp\Entities\Option;
use MangoFp\Entities\Steps;
use MangoFp\Entities\Template;

class SettingsUseCase {
    public function __construct(iOutput $output, iStorage $storage) {
        $this->output = $output;
        $this->storage = $storage;
    }

    public function fetchAllLabelsToOutput() {
        $labels = $this->storage->fetchLabels();

        if (!$labels || !\is_array($labels)) {
            $labels = [];
            //return $this->output->outputError('ERROR => unable to read labels list', iOutput::ERROR_FAILED);
        }

        $data = [];
        foreach ($labels as $label) {
            $data[] = [
                'id' => $label->get('id'),
                'name' => $label->get('labelName'),
            ];
        }

        return $this->output->outputResult(['labels' => $data]);
    }

    public function fetchStepsOrDefaultAsSteps() {
        $stepsOption = $this->storage->fetchOption(Option::OPTION_STEPS);
        $stepsObj = new Steps();
        if (!$stepsOption) {
            $stepsObj->setDataAsInitialSteps();
        } else {
            $stepsObj->setDataFromArray($stepsOption->getDataAsArray(), true);
        }

        return $stepsObj;
    }

    public function fetchAllStepsToOutput() {
        try {
            $stepsObj = $this->fetchStepsOrDefaultAsSteps();

            return $this->makeStepsOutput($stepsObj);
        } catch (\Exception $err) {
            return $this->output->outputError(
                'Error fetching steps: '.$err->getMessage(),
                iOutput::ERROR_NOTFOUND
            );
        }
    }

    public function updateOrInsertStepAndReturnAllSteps($stepData) {
        $stepsObj = $this->fetchStepsOrDefaultAsSteps();

        try {
            if (isset($stepData['code'])) {
                $stepsObj->updateStep($stepData['code'], $stepData);
            } else {
                $stepsObj->appendStep($stepData);
            }
        } catch (\Exception $err) {
            return $this->output->outputError(
                'Error updating or inserting step: '.$err->getMessage(),
                iOutput::ERROR_NOTFOUND
            );
        }

        if (!$this->storage->storeOption($stepsObj)) {
            return $this->output->outputError(
                'Error storing steps',
                iOutput::ERROR_NOTFOUND
            );
        }

        return $this->makeStepsOutput($stepsObj);
    }

    public function updateOrInsertTemplate($templateParams) {
        //error_log('Will update email template:');
        //error_log(json_encode($templateParams));
        //return $this->output->outputResult([]);
        if (!$templateParams['code']) {
            return $this->output->outputError(
                'No code for template update',
                iOutput::ERROR_FAILED
            );
        }

        $template = new Template();
        $template->setDataFromArray([
            'code' => $templateParams['code'],
            'template' => isset($templateParams['templateText']) ? $templateParams['templateText'] : '',
            'addresses' => (
                isset($templateParams['emails']) && is_array($templateParams['emails'])
            ) ?
                $templateParams['emails'] :
                [],
            'mainAddresses' => (
                isset($templateParams['primaryEmails']) && is_array($templateParams['primaryEmails'])
            ) ?
                $templateParams['primaryEmails'] :
                ['[contactEmail]'],
        ]);

        $success = $this->storage->storeTemplate($template);
        if (!$success) {
            return $this->output->outputError('Failed to store template', iOutput::ERROR_FAILED);
        }

        return $this->output->outputResult(['success' => 'Template saved']);
    }

    public function doWithStep($operation, $code) {
        $stepsObj = $this->fetchStepsOrDefaultAsSteps();

        try {
            switch ($operation) {
                case 'delete':
                    $stepsObj->deleteStep($code);

                    break;

                case 'moveup':
                    $stepsObj->moveStep($code, Steps::ORDER_UP);

                    break;

                case 'movedown':
                    $stepsObj->moveStep($code, Steps::ORDER_DOWN);

                    break;

                default:
                    return $this->output->outputError(
                        'Action not allowed: '.$operation,
                        iOutput::ERROR_NOTFOUND
                    );

                    break;
            }
        } catch (\Exception $err) {
            return $this->output->outputError(
                'Failed to '.$operation.' step: '.$err->getMessage(),
                iOutput::ERROR_NOTFOUND
            );
        }

        if (!$this->storage->storeOption($stepsObj)) {
            return $this->output->outputError(
                'Error storing steps',
                iOutput::ERROR_NOTFOUND
            );
        }

        return $this->makeStepsOutput($stepsObj);
    }

    public function fetchAllTemplatesToOutput() {
        $templates = Template::getDefaultTemplates();
        $allTemplates = $this->storage->fetchTemplates();

        foreach ($allTemplates as $templateObj) {
            $templates[$templateObj->get('code')] = $this->getTemplateDataForOutput($templateObj);
        }

        return $this->output->outputResult(
            [
                'templates' => $templates,
            ]
        );
    }

    public function fetchTemplateToOutput($templateCode) {
        $templateObj = $this->storage->fetchTemplate($templateCode);
        $templateData = false;

        if ($templateObj) {
            $templateData = $this->getTemplateDataForOutput($templateObj);
        } elseif (
            !$templateObj
            && isset(Template::getDefaultTemplates()[$templateCode])
        ) {
            $templateData = Template::getDefaultTemplates()[$templateCode];
        }

        if ($templateData) {
            return $this->output->outputResult(
                [
                    'template' => [$templateCode => $templateData],
                ]
            );
        }

        return $this->output->outputResult([]);
    }

    public function storeOption(array $payload) {
        if (!isset($payload['key'])) {
            return $this->output->outputError('key is mandatory', iOutput::ERROR_FAILED);
        }

        if (!Option::isValidOption($payload['key'])) {
            return $this->output->outputError(
                __('Not allowed option: ').$payload['key'],
                iOutput::ERROR_FAILED
            );
        }

        $optionObj = new Option();
        $optionObj->setDataFromArray(
            [
                'key' => $payload['key'],
                'value' => $payload['value'],
            ]
        );

        $success = $this->storage->storeOption($optionObj);
        if (!$success) {
            return $this->output->outputError(__('Failed to store option'), iOutput::ERROR_FAILED);
        }

        return $this->output->outputResult(['success' => __('Setting saved')]);
    }

    public function storeOptionsAndMakeAllOptionsOutput(array $payload) {
        if (!isset($payload['options'])) {
            return $this->output->outputError(__('No options in request'), iOutput::ERROR_FAILED);
        }
        if (!is_array($payload['options'])) {
            return $this->output->outputError(__('Options should be array'), iOutput::ERROR_FAILED);
        }

        $inValidOptionFound = false;
        $lastError = '';

        foreach ($payload['options'] as $optionInPayload) {
            if (!isset($optionInPayload['key'])) {
                $lastError = __('Option without a key in request');
                $inValidOptionFound = true;

                return;
            }

            if (!isset($optionInPayload['value'])) {
                $lastError = __('Option without a value in request');
                $inValidOptionFound = true;

                return;
            }

            if (!Option::isValidOption($optionInPayload['key'])) {
                $lastError = sprintf(__('Not valid option: %s'), $optionInPayload['key']);
                $inValidOptionFound = true;

                return;
            }
        }

        if ($inValidOptionFound) {
            return $this->output->outputError($lastError, iOutput::ERROR_FAILED);
        }

        $errors = [];

        \error_log(print_r($payload, true));

        foreach ($payload['options'] as $optionInPayload) {
            $optionObj = new Option();
            $optionObj->setDataFromArray(
                [
                    'key' => $optionInPayload['key'],
                    'value' => $optionInPayload['value'],
                ]
            );

            $success = $this->storage->storeOption($optionObj);
            if (!$success) {
                $errors[] = \sprintf(__('Failed to store option %s '), $optionInPayload['key']);
            }
        }

        return $this->makeAllOptionsOutput($errors);
    }

    public function getOptionObj(string $optionKey) {
        $optionObj = $this->storage->fetchOption($optionKey);
        $optionValue = '';
        
        if (!$optionObj) {
            switch ($optionKey) {
                case Option::OPTION_REPLY_EMAIL:
                        $optionValue = $this->storage->getAdminEmail();

                    break;

                case Option::OPTION_LABEL_FIELD:
                        $optionValue = '[pageTitle]';

                    break;

                case Option::OPTION_EMAIL_FIELD:
                        $optionValue = 'your-email';

                    break;
                case Option::OPTION_REPLY_EMAIL_NAME:
                        $optionValue = 'MangoFp';

                    break;
            }

            $optionObj = new Option([
                'key' => $optionKey,
                'value' => $optionValue,
            ]);
        }

        return $optionObj;
    }

    public function makeOptionOutput(string $key) {
        if (!Option::isValidOption($key)) {
            return $this->output->outputError(
                'Not allowed option '.$key,
                iOutput::ERROR_FAILED
            );
        }

        try {
            $optionObj = $this->getOptionObj($key);
        } catch (\Exception $err) {
            return $this->output->outputError($err->getMessage(), iOutput::ERROR_FAILED);
        }

        return [
            'key' => $optionObj->get('key'),
            'value' => $optionObj->get('value'),
        ];
    }

    public function makeAllOptionsOutput($messages = []) {
        try {
            $allOptions = [];
            foreach (Option::getAllOptionsDefinitions() as $optionKey => $optionDef) {
                $optionObj = $this->getOptionObj($optionKey);
                $allOptions[$optionKey] = [
                    'label' => $optionDef['label'],
                    'type' => $optionDef['type'],
                    'name' => $optionDef['name'],
                    'hint' => $optionDef['hint'],
                    'value' => $optionObj->get('value'),
                ];
            }
            $output = [
                'options' => $allOptions,
            ];

            if ($messages) {
                $output['errors'] = $messages;
            }

            return $this->output->outputResult($output);
        } catch (\Exception $err) {
            return \sprintf(__('Error while fetching options: %s'), $err->getMessage());
        }
    }

    protected function getTemplateDataForOutput($templateObj) {
        return [
            'template' => $templateObj->get('template'),
            'addresses' => $templateObj->get('addresses'),
            'mainAddresses' => $templateObj->get('mainAddresses'),
        ];
    }

    protected function makeStepsOutput(Steps $stepsObj) {
        try {
            $result = [
                'steps' => $stepsObj->getDataAsOrderedObject(),
            ];

            return $this->output->outputResult($result);
        } catch (\Exception $err) {
            return $this->output->outputError(
                'Error parsing steps output: '.$err->getMessage(),
                iOutput::ERROR_NOTFOUND
            );
        }
    }
}
