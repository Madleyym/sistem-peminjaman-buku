<?php
$logFile = 'sistem/logs/js-error.log';
$errorData = json_decode(file_get_contents('php://input'), true);

if ($errorData) {
    $logMessage = date('[Y-m-d H:i:s]') .
        " Error: {$errorData['message']} " .
        "File: {$errorData['filename']} " .
        "Line: {$errorData['lineno']}\n" .
        "Stack Trace: {$errorData['stack']}\n\n";

    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
