<?php

define('EXEC_START', microtime(true));
// System time zone
date_default_timezone_set('Africa/Dar_es_Salaam');
// Session globalization
session_start();
//require "vendor/autoload.php";
/*$robo = 'robot@example.com';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;*/
include realpath(__DIR__).'/rand/index.php';
/*die;
$developmentMode = true;
$mailer = new PHPMailer($developmentMode);
try {
    $mailer->SMTPDebug = 2;
    $mailer->isSMTP();
    if ($developmentMode) {
    $mailer->SMTPOptions = [
        'ssl'=> [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
        ]
    ];
    }
    $mailer->Host = 'mail.example.com';
    $mailer->SMTPAuth = true;
    $mailer->Username = 'robot@example.com';
    $mailer->Password = 'password';
    $mailer->SMTPSecure = 'tls';
    $mailer->Port = 587;
    $mailer->setFrom('robot@example.com', 'Name of sender');
    $mailer->addAddress('joe@example.com', 'Name of recipient');
    $mailer->isHTML(true);
    $mailer->Subject = 'PHPMailer Test';
    $mailer->Body = 'This is a <b>SAMPLE<b> email sent through <b>PHPMailer<b>';
    $mailer->send();
    $mailer->ClearAllRecipients();
    echo "MAIL HAS BEEN SENT SUCCESSFULLY";
} catch (Exception $e) {
    echo "EMAIL SENDING FAILED. INFO: " . $mailer->ErrorInfo;
}*/