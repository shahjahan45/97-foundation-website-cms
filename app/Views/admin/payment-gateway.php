<?php
$enabled=(int)($gateway['enabled']??0)===1;$mode=$gateway['mode']??'sandbox';
$lastOk=($gateway['last_test_status']??'never')==='success';
$storageLabel=($vaultSource??'')==='server_environment'?'Server environment':((($vaultSource??'')==='private_storage')?'Private encrypted storage':'Not ready');
?>
<div class="page-actions payment-setup-page-head"><div><h2>Easy Online Payment Setup</h2><p>Developer Integration · Connect PayHere in a few simple steps. Advanced API features stay optional.</p></div><div class="payment-head-actions"><a class="outline-link" href="<?=url('admin/online-payments')?>">Online Transactions</a><a class="outline-link" href="<?=url('donate?tab=online')?>" target="_blank">Preview Donation Page ↗</a></div></div>

<div class="easy-payment-overview">
  <section class="admin-panel easy-payment-status">
    <div class="easy-provider-mark">P</div>
    <div class="easy-provider-copy"><small>PAYMENT PROVIDER</small><h3>PayHere Sri Lanka</h3><p>Hosted checkout · LKR · card/payment details stay with the gateway</p></div>
    <div class="easy-status-pills"><span class="gateway-mode <?=e($mode)?>"><?=e($mode==='live'?'LIVE':'SANDBOX')?></span><span class="easy-health <?=$enabled?'ready':'muted'?>"><?=$enabled?'Online payments enabled':'Online payments disabled'?></span></div>
  </section>
  <section class="admin-panel easy-readiness-card">
    <div class="easy-readiness-item <?=$vaultReady?'done':'warn'?>"><i><?=$vaultReady?'✓':'!'?></i><div><b>Secure secret storage</b><span><?=e($storageLabel)?></span></div></div>
    <div class="easy-readiness-item <?=$checkoutReady?'done':'pending'?>"><i><?=$checkoutReady?'✓':'2'?></i><div><b>Checkout credentials</b><span><?=$checkoutReady?'Merchant ID + Secret configured':'Add Merchant ID + Secret below'?></span></div></div>
    <div class="easy-readiness-item <?=$lastOk?'done':'pending'?>"><i><?=$lastOk?'✓':'3'?></i><div><b>Setup validation</b><span><?=$lastOk?'Last validation passed':'Use Save & Test Setup'?></span></div></div>
  </section>
</div>

<form method="post" action="<?=url('admin/developer/payment-gateway/save')?>" class="admin-panel easy-payment-wizard"><?=csrf_field()?>
  <header class="easy-wizard-head"><div><span>QUICK SETUP</span><h3>Only the checkout credentials are required</h3><p>Start in Sandbox. Add the two credentials from your PayHere merchant account, save and test, then enable online donations. Secrets are encrypted server-side and never exposed to donors.</p></div><span class="easy-secure-chip">🔒 Server-side only</span></header>

  <div class="easy-wizard-step">
    <span class="easy-step-number">1</span><div class="easy-step-content"><div class="easy-step-title"><b>Choose payment environment</b><small>Use Sandbox while testing. Switch to Live only after the full flow works on HTTPS.</small></div>
      <div class="easy-mode-choice">
        <label class="easy-mode-card"><input type="radio" name="mode" value="sandbox" <?=$mode==='sandbox'?'checked':''?>><span><b>Sandbox / Test</b><small>Safe testing with no real charge</small></span></label>
        <label class="easy-mode-card"><input type="radio" name="mode" value="live" <?=$mode==='live'?'checked':''?>><span><b>Live / Production</b><small>Real donor payments · HTTPS required</small></span></label>
      </div>
    </div>
  </div>

  <div class="easy-wizard-step">
    <span class="easy-step-number">2</span><div class="easy-step-content"><div class="easy-step-title"><b>Enter PayHere checkout credentials</b><small>For normal online donations, you only need Merchant ID and Merchant Secret.</small></div>
      <div class="easy-credential-grid">
        <label><span>Merchant ID *</span><input name="merchant_id" maxlength="100" value="<?=e($gateway['merchant_id']??'')?>" autocomplete="off" placeholder="Enter PayHere Merchant ID"><small>Found in your PayHere merchant account.</small></label>
        <label><span>Merchant Secret *</span><input type="password" name="merchant_secret" autocomplete="new-password" placeholder="<?=($runtime['merchant_secret']??'')?'Leave blank to keep saved secret':'Enter Merchant Secret'?>"><small>Current: <?=e($merchantSecretMask)?></small></label>
      </div>
      <div class="easy-callback-box"><span>Webhook URL</span><code><?=e(absolute_url('payments/payhere/notify'))?></code><small>Copy this URL into the gateway configuration when PayHere asks for the server callback/notify URL.</small></div>
    </div>
  </div>

  <div class="easy-wizard-step">
    <span class="easy-step-number">3</span><div class="easy-step-content"><div class="easy-step-title"><b>Set donation limits and availability</b><small>These controls apply immediately to the public Online Payment form.</small></div>
      <div class="easy-limit-grid"><label><span>Minimum Donation (LKR)</span><div class="easy-money-input"><b>LKR</b><input type="number" min="1" step="0.01" name="min_amount" value="<?=e((string)($gateway['min_amount']??100))?>"></div></label><label><span>Maximum Donation (LKR)</span><div class="easy-money-input"><b>LKR</b><input type="number" min="1" step="0.01" name="max_amount" value="<?=e((string)($gateway['max_amount']??1000000))?>"></div></label></div>
      <label class="easy-enable-switch"><input type="checkbox" name="enabled" value="1" <?=$enabled?'checked':''?>><span><b>Enable Online Payments</b><small>Donors will see the Online Payment option when checkout credentials are ready.</small></span></label>
    </div>
  </div>

  <details class="easy-advanced-settings">
    <summary><span><b>Advanced Settings</b><small>Optional — status sync, refunds, receipt settings and Merchant API access</small></span><i>+</i></summary>
    <div class="easy-advanced-body">
      <div class="easy-credential-grid"><label><span>Merchant API App ID</span><input name="api_app_id" maxlength="160" value="<?=e($gateway['api_app_id']??'')?>" autocomplete="off"><small>Only needed for status checking and refunds.</small></label><label><span>Merchant API App Secret</span><input type="password" name="api_app_secret" autocomplete="new-password" placeholder="Leave blank to keep saved secret"><small>Current: <?=e($appSecretMask)?></small></label></div>
      <div class="easy-credential-grid"><label><span>Receipt Prefix</span><input name="receipt_prefix" maxlength="16" value="<?=e($gateway['receipt_prefix']??'97D')?>"></label><label class="easy-inline-toggle"><input type="checkbox" name="receipt_email_enabled" value="1" <?=((int)($gateway['receipt_email_enabled']??0)===1)?'checked':''?>><span>Send receipt email when server mail is available</span></label></div>
      <div class="easy-endpoints"><p><span>Return URL</span><code><?=e(absolute_url('donate/payment/return'))?></code></p><p><span>Cancel URL</span><code><?=e(absolute_url('donate/payment/cancel'))?></code></p></div>
    </div>
  </details>

  <footer class="easy-payment-actions"><button class="admin-btn easy-primary-action" type="submit" name="save_action" value="save_test">Save & Test Setup</button><button class="outline-link" type="submit" name="save_action" value="save">Save Without Testing</button><span>Your secret is stored using AES-256-GCM in <?=e(strtolower($storageLabel))?>.</span></footer>
</form>

<div class="easy-payment-bottom-grid">
  <section class="admin-panel easy-howto"><div class="panel-head"><div><h3>Simple activation process</h3><span>You do not need the advanced API credentials for normal hosted checkout.</span></div></div><ol><li><b>Create/prepare PayHere merchant account</b><span>Get the Merchant ID and Merchant Secret.</span></li><li><b>Keep Sandbox selected</b><span>Save & Test Setup, then make a test donation from the public page.</span></li><li><b>Verify callback and success page</b><span>Confirm the transaction becomes Successful in Admin → Online Payments.</span></li><li><b>Move to Live</b><span>Only after HTTPS and all sandbox checks are complete.</span></li></ol></section>
  <section class="admin-panel easy-test-summary"><div class="panel-head"><div><h3>Last setup test</h3><span><?=e($gateway['last_test_at']?date('d M Y · h:i A',strtotime($gateway['last_test_at'])):'Not tested yet')?></span></div></div><div class="easy-last-test <?=$lastOk?'success':'neutral'?>"><i><?=$lastOk?'✓':'•'?></i><div><b><?=e($lastOk?'Setup validation passed':ucfirst((string)($gateway['last_test_status']??'Not tested')))?></b><p><?=e($gateway['last_test_message']?:'Use Save & Test Setup after entering your Merchant ID and Merchant Secret.')?></p></div></div><form method="post" action="<?=url('admin/developer/payment-gateway/test')?>"><?=csrf_field()?><button class="outline-link full" type="submit">Run Setup Test Again</button></form><?php if($mode==='sandbox'&&$enabled&&$checkoutReady):?><a class="admin-btn full easy-open-test" href="<?=url('donate?tab=online')?>" target="_blank">Open Sandbox Donation Test ↗</a><?php endif;?></section>
</div>
