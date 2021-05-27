<?php
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/jokulo2o.php');

$jokulo2o = new JokulO2O();

if (!$_POST) {
    header('Location: javascript:history.go(-1)');
    die;
}

$invoiceNumber = $_POST['invoice_number'];
$amount = $_POST['amount'];
$orderId = $_POST['order_id'];


# generate CheckSum
$data = array(
    "order" => array(
        "invoice_number" => $invoiceNumber,
        "amount" => $amount
    ),
    "online_to_offline_info" => array(
        "expired_time" => $_POST['EXP_TIME'],
        "reusable_status" => false,
        "info1" => ''
    ),
    "alfa_info" => array(
        "receipt" => array(
            "footer_message" => $_POST['JOKULO2O_FOOTER_MESSAGE']
        )
    ),
    "customer" => array(
        "name" => $_POST['NAME'],
        "email" => $_POST['EMAIL']
    ),
    "additional_info" => array(
        "integration" => array(
            "name" => "prestashop-plugin",
            "module-name" => "jokul-o2o",
            "version"=> "1.0.0"
        )
    )
);

$config = $jokulo2o->getServerConfig();
$bodyJson = json_encode($data);
$dataBody = str_replace(array("\r", "\n"), array("\\r", "\\n"), $bodyJson);
$digest = base64_encode(hash("sha256", $dataBody, True));

$requestId = $_POST['REGID'];
$clientId = $config['CLIENT_ID'];
$requestTimestamp = $_POST['DATETIME'];

$paymentChannel = $_POST['PAYMENTCHANNEL'];
$requestTarget = "";
if ($paymentChannel == "ALFA") {
    $requestTarget = "/alfa-online-to-offline/v2/payment-code";
}

$dataWords = "Client-Id:" . $clientId . "\n" .
    "Request-Id:" . $requestId . "\n" .
    "Request-Timestamp:" . $requestTimestamp . "\n" .
    "Request-Target:" . $requestTarget . "\n" .
    "Digest:" . $digest;


$signature = base64_encode(hash_hmac('SHA256', htmlspecialchars_decode($dataWords), htmlspecialchars_decode($config['SHARED_KEY']), True));

$configarray = parse_ini_file($_POST['CUSTOMERID']);
$URL = $configarray[$paymentChannel];

define('POSTURL', $URL);

$ch = curl_init(POSTURL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FAILONERROR, false);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Signature:' . "HMACSHA256=" . $signature,
    'Request-Id:' . $requestId,
    'Client-Id:' . $clientId,
    'Request-Timestamp:' . $requestTimestamp
));

$GETDATARESULT = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error_msg = curl_error($ch);
$myservername = Tools::getHttpHost(true) . __PS_BASE_URI__;
$GETDATARESULT = json_decode($GETDATARESULT);
if ($httpcode == 200) {
    $PAYMENTCODE = $GETDATARESULT->online_to_offline_info->payment_code;
    $PAYMENTEXP = $GETDATARESULT->online_to_offline_info->expired_date;
    $JOKULO2O_PAYMENTHOW = $GETDATARESULT->online_to_offline_info->how_to_pay_page;
    $STATUSCODE = '';

    curl_close($ch);
?>

    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loading...</title>
        <link rel="stylesheet" href="https://cdn-doku.oss-ap-southeast-5.aliyuncs.com/doku-ui-framework/css-doku%401.0.0/css/main.css">
        <link rel="stylesheet" href="https://cdn-doku.oss-ap-southeast-5.aliyuncs.com/doku-ui-framework/css-jokul/1.0.0/main.css">
    </head>

    <body onload="document.formRedirect.submit()">
        <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
            <div class="spinner-border text-light spinner-lg" role="status"></div>
            <form action="<?php echo $myservername; ?>index.php?fc=module&module=jokulo2o&controller=request&task=redirect" method="POST" id="formRedirect" name="formRedirect">
                <input type="hidden" name="DATABODY" value="<?php echo $bodyJson; ?>">
                <input type="hidden" name="AMOUNT" value="<?php echo $amount; ?>">
                <input type="hidden" name="TRANSIDMERCHANT" value="<?php echo $invoiceNumber; ?>">
                <input type="hidden" name="ORDERID" value="<?php echo $orderId; ?>">
                <input type="hidden" name="STATUSCODE" value="<?php echo $STATUSCODE; ?>">
                <input type="hidden" name="PAYMENTCODE" value="<?php echo $PAYMENTCODE; ?>">
                <input type="hidden" name="PAYMENTEXP" value="<?php echo $PAYMENTEXP; ?>">
                <input type="hidden" name="JOKULO2O_PAYMENTHOW" value="<?php echo $JOKULO2O_PAYMENTHOW; ?>">
                <input type="hidden" name="PAYMENTCHANNEL" value="<?php echo $paymentChannel; ?>">
            </form>
        </div>
    </body>

    </html>
<?php
} else {
    curl_close($ch);
?>
    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loading...</title>
        <link rel="stylesheet" href="https://cdn-doku.oss-ap-southeast-5.aliyuncs.com/doku-ui-framework/css-doku%401.0.0/css/main.css">
        <link rel="stylesheet" href="https://cdn-doku.oss-ap-southeast-5.aliyuncs.com/doku-ui-framework/css-jokul/1.0.0/main.css">
    </head>

    <body onload="document.formRedirect.submit()">
        <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
            <div class="spinner-border text-light spinner-lg" role="status"></div>
            <form action="<?php echo $myservername; ?>index.php?fc=module&module=jokulo2o&controller=request&task=redirectFailed" method="POST" id="formRedirect" name="formRedirect">
                <input type="hidden" name="PAYMENTCHANNEL" value="<?php echo $paymentChannel; ?>">
            </form>
        </div>
    </body>

    </html>
<?php
}
?>
