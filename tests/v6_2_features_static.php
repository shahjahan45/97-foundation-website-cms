<?php
$root=dirname(__DIR__);$checks=[];
$routes=file_get_contents($root.'/index.php');$donate=file_get_contents($root.'/app/Views/donate.php');$layout=file_get_contents($root.'/app/Views/layouts/admin.php');$schema=file_get_contents($root.'/database/schema.sql');$service=file_get_contents($root.'/app/Payments/PaymentService.php');$gateway=file_get_contents($root.'/app/Payments/Gateways/PayHereGateway.php');$vault=file_get_contents($root.'/app/Payments/PaymentSecretVault.php');$admin=file_get_contents($root.'/app/Views/admin/payment-gateway.php');$transactions=file_get_contents($root.'/app/Views/admin/online-payments.php');$config=file_get_contents($root.'/config.php');
$checks['Public bank and online donation tabs']=str_contains($donate,'data-donation-tab="bank"')&&str_contains($donate,'data-donation-tab="online"')&&str_contains($donate,'LKR 5,000');
$checks['Online payment routes']=str_contains($routes,"/donate/online/review")&&str_contains($routes,"/payments/payhere/notify")&&str_contains($routes,"/donate/payment/receipt/{public}");
$checks['Developer Integration route and menu']=str_contains($routes,"/admin/developer/payment-gateway")&&str_contains($layout,'Developer Integration')&&str_contains($layout,'Payment Gateway');
$checks['Online transaction management']=str_contains($routes,"/admin/online-payments")&&str_contains($transactions,'Export CSV')&&str_contains($transactions,'All statuses');
$checks['Modular gateway architecture']=is_file($root.'/app/Payments/GatewayInterface.php')&&is_file($root.'/app/Payments/PaymentGatewayFactory.php')&&str_contains($gateway,'implements GatewayInterface');
$checks['Server callback signature verification']=str_contains($gateway,'md5sig')&&str_contains($gateway,'hash_equals')&&str_contains($service,'PAYMENT_CALLBACK_VERIFIED');
$checks['Duplicate webhook protection']=str_contains($schema,'event_hash CHAR(64) NOT NULL UNIQUE')&&str_contains($service,'payment_webhook_events');
$checks['Encrypted server-side secrets']=str_contains($vault,'aes-256-gcm')&&str_contains($config,'PAYMENT_ENCRYPTION_KEY')&&str_contains($admin,'Secrets are encrypted server-side');
$checks['No sensitive card columns']=!preg_match('/\b(card_number|card_no|cvv|pin|card_expiry)\b/i',$schema);
$checks['LKR min/max controls']=str_contains($schema,'min_amount')&&str_contains($schema,'max_amount')&&str_contains($admin,'Minimum Donation (LKR)')&&str_contains($admin,'Maximum Donation (LKR)');
$checks['Payment statuses']=str_contains($schema,"ENUM('pending','processing','successful','failed','cancelled','refunded')");
$checks['Approved donation finalization']=str_contains($service,'"online_payment"')&&str_contains($service,'"approved"')&&str_contains($service,'finalizeSuccess');
$checks['CSP allows hosted PayHere checkout']=str_contains($config,"form-action 'self' https://www.payhere.lk https://sandbox.payhere.lk");
$ok=true;foreach($checks as $name=>$pass){echo ($pass?'PASS':'FAIL')." - $name\n";$ok=$ok&&$pass;}exit($ok?0:1);
