<?php
namespace MangoFp\UseCases;

interface iOutput {
    const ERROR_VALIDATION = 'ERROR_VALIDATION';
    const ERROR_NOTFOUND = 'ERROR_NOTFOUND';
    const ERROR_FAILED = 'ERROR_FAILED';
    const RESULT_SUCCESS = 'RESULT_SUCCESS';
    const RESULT_ERROR = 'RESULT_ERROR';
    public function outputResult(array $data);
    public function outputError(string $message, string $errorCode);
} 