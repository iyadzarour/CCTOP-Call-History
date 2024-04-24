<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// mail info
$from = "test@paketomat.at";
$to = "iyad.zarour@gwp.dpd.at";
$cc = "";
$bcc = "";
$replyTo = "";
$subject = "Test Mail";
$bodyHtml = "<h1>Test Mail</h1><p>This is a test mail</p>";
$bodyText = "Test Mail\nThis is a test mail";


$wsdl = "http://172.21.233.10/WebServicesSOAP/sendMailServer.php?wsdl";
$dataArgs = array($from, $to, $cc, $bcc, $replyTo, $subject, $bodyHtml, $bodyText, null, null);

$soap = new SoapClient($wsdl, ['encoding' => 'UTF-8']);
$ret = $soap->__call('make_mail', $dataArgs);

?>
