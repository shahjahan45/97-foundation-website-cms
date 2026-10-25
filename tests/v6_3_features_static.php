<?php
$root=dirname(__DIR__);$view=file_get_contents($root.'/app/Views/admin/payment-gateway.php');$donate=file_get_contents($root.'/app/Views/donate.php');$vault=file_get_contents($root.'/app/Payments/PaymentSecretVault.php');$controller=file_get_contents($root.'/app/Controllers/Admin/PaymentGatewayAdminController.php');
$checks=[
 'Easy payment setup wizard'=>str_contains($view,'QUICK SETUP')&&str_contains($view,'Save & Test Setup')&&str_contains($view,'Only the checkout credentials are required'),
 'Basic checkout only needs merchant credentials'=>str_contains($view,'Merchant ID *')&&str_contains($view,'Merchant Secret *')&&str_contains($view,'Advanced Settings'),
 'Advanced API credentials optional'=>str_contains($view,'Only needed for status checking and refunds.'),
 'Automatic private encryption key'=>str_contains($vault,'storage/private/payment.key')&&str_contains($vault,'random_bytes(32)')&&str_contains($vault,'aes-256-gcm'),
 'Save and test controller flow'=>str_contains($controller,"save_action")&&str_contains($controller,'save_test')&&str_contains($controller,'testConnection'),
 'Simplified public online donation'=>str_contains($donate,'Donate online in three simple steps')&&str_contains($donate,'Additional donor details')&&str_contains($donate,'Review & Continue to Payment'),
 'Payment preference removed from public quick flow'=>!str_contains($donate,'name="payment_preference"')
];
$ok=true;foreach($checks as $name=>$pass){echo ($pass?'PASS':'FAIL')." - $name\n";$ok=$ok&&$pass;}exit($ok?0:1);
