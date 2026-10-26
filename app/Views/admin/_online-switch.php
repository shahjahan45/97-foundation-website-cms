<?php
/** V7.2 — Online payments On / Off card. Include with $switchReturn set to the current admin path. */
$opRuntime = \App\Payments\PaymentConfig::runtime();
$opOn = (int)($opRuntime['enabled'] ?? 0) === 1;
$opReady = trim((string)$opRuntime['merchant_id']) !== '' && trim((string)$opRuntime['merchant_secret']) !== '';
$opLive = ($opRuntime['mode'] ?? 'sandbox') === 'live';
$opCanManage = \App\Core\Auth::can('manage_payment_gateway');
$switchReturn = $switchReturn ?? 'admin/developer/payment-gateway';
?>
<section class="admin-panel v7-switch-card <?= $opOn ? 'is-on' : 'is-off' ?>" aria-labelledby="online-switch-title">
  <form method="post" action="<?= url('admin/developer/payment-gateway/toggle') ?>" class="v7-fr-form" data-switch-form>
    <?= csrf_field() ?><input type="hidden" name="return" value="<?= e($switchReturn) ?>">
    <div class="v7-fr-state">
      <span class="v7-fr-dot" aria-hidden="true"></span>
      <div>
        <h3 id="online-switch-title">Online card payments are <?= $opOn ? 'on' : 'off' ?> <span class="v7-mode-chip <?= $opLive ? 'is-live' : '' ?>"><?= $opLive ? 'Live' : 'Sandbox' ?></span></h3>
        <p><?php if ($opOn): ?>Donors can pay by card through PayHere on the Donate page<?= $opLive ? '.' : ' — Sandbox is for testing only, no real money moves.' ?><?php elseif (!$opReady): ?>Add your PayHere Merchant ID and Merchant Secret in Easy Payment Setup, then turn online payments on.<?php else: ?>The Online Payment option on the Donate page shows as unavailable. Bank transfer donations still work.<?php endif; ?></p>
      </div>
    </div>
    <?php if ($opCanManage): ?>
    <div class="v7-fr-controls">
      <fieldset class="v7-segment" aria-label="Online card payments">
        <label><input type="radio" name="enabled" value="1" <?= $opOn ? 'checked' : '' ?> <?= !$opReady && !$opOn ? 'disabled' : '' ?>><span>On</span></label>
        <label><input type="radio" name="enabled" value="0" <?= $opOn ? '' : 'checked' ?>><span>Off</span></label>
      </fieldset>
      <button class="admin-btn" type="submit" <?= !$opReady && !$opOn ? 'disabled title="Add the PayHere credentials first"' : '' ?>>Save</button>
    </div>
    <?php else: ?>
    <span class="status-badge <?= $opOn ? 'active' : 'inactive' ?>"><?= $opOn ? 'On' : 'Off' ?></span>
    <?php endif; ?>
  </form>
</section>
