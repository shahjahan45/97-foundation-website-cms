<?php
$status = $transaction['status'];
$success = $status === 'successful';
$pending = in_array($status, ['pending', 'processing'], true);
$cancel = $status === 'cancelled';
$refunded = $status === 'refunded';
$logo = foundation_logo_url(false);
$foundationName = setting('foundation', 'name', '97 Foundation');
$tagline = setting('foundation', 'tagline', 'Hope. Help. Humanity.');
$title = $success
    ? 'Donation confirmed'
    : ($pending
        ? 'Payment verification in progress'
        : ($cancel
            ? 'Payment cancelled'
            : ($refunded ? 'Donation refunded' : 'Payment unsuccessful')));
$message = $success
    ? 'Thank you for supporting 97 Foundation. Your payment has been securely verified.'
    : ($pending
        ? 'The gateway has not yet completed server-side verification. This page can be refreshed safely.'
        : ($cancel
            ? 'No successful donation was recorded. You can safely try again.'
            : ($refunded
                ? 'This payment has been marked as refunded.'
                : 'This payment was not marked successful. No online donation was counted.')));
$icon = $success ? '✓' : ($pending ? '⏳' : ($refunded ? '↺' : '!'));
?>
<section class="payment-flow-page">
  <div class="container">
    <article class="payment-result-card glass-panel status-<?=e($status)?>">
      <header class="payment-result-brand">
        <div class="payment-result-brand-mark <?=$pending ? 'is-pending' : ''?>">
          <?php if($logo): ?>
            <img src="<?=e($logo)?>" alt="<?=e($foundationName)?> logo">
          <?php else: ?>
            <span>97</span>
          <?php endif; ?>
        </div>
        <div class="payment-result-brand-copy">
          <small>97 FOUNDATION ONLINE DONATION</small>
          <strong><?=e($foundationName)?></strong>
          <span><?=e($tagline)?></span>
        </div>
      </header>

      <div class="payment-result-status-head">
        <div class="payment-result-icon"><?=$icon?></div>
        <span class="eyebrow green-text">Secure payment status</span>
      </div>

      <h1><?=$title?></h1>
      <p><?=$message?></p>

      <div class="payment-result-amount">
        <small>Amount</small>
        <strong><?=money($transaction['amount'])?></strong>
      </div>

      <dl>
        <div><dt>Donation Reference</dt><dd><?=e($transaction['public_id'])?></dd></div>
        <div><dt>Gateway Order</dt><dd><?=e($transaction['gateway_order_id'])?></dd></div>
        <?php if($transaction['gateway_payment_id']): ?><div><dt>Gateway Transaction ID</dt><dd><?=e($transaction['gateway_payment_id'])?></dd></div><?php endif; ?>
        <?php if($transaction['receipt_number']): ?><div><dt>Receipt Number</dt><dd><?=e($transaction['receipt_number'])?></dd></div><?php endif; ?>
        <div><dt>Status</dt><dd><span class="payment-status-pill <?=e($status)?>"><?=e(ucfirst($status))?></span></dd></div>
        <div><dt>Verification</dt><dd><?= $success ? 'Verified by server callback' : ($pending ? 'Awaiting server confirmation' : ($refunded ? 'Transaction updated after refund' : 'Not verified as successful')) ?></dd></div>
      </dl>

      <?php if($pending): ?>
        <section class="payment-verification-guide">
          <h3>How verification works</h3>
          <ul>
            <li>The payment gateway sends a secure server-to-server callback after checkout.</li>
            <li>This page does not mark the donation successful until the callback or status check is verified.</li>
            <li>You can safely use <b>Refresh Status</b> to re-check the latest result.</li>
            <li>If the status stays pending, the administrator can verify it from the Online Payments panel.</li>
          </ul>
        </section>
      <?php endif; ?>

      <div class="payment-result-actions">
        <?php if($success || $refunded): ?>
          <a class="btn btn-primary" href="<?=url('donate/payment/receipt/'.$transaction['public_id'])?>" target="_blank">Open Donation Receipt</a>
        <?php elseif($pending): ?>
          <a class="btn btn-primary" href="<?=e(url('donate/payment/return?ref='.rawurlencode($transaction['public_id'])))?>">Refresh Status</a>
        <?php else: ?>
          <a class="btn btn-primary" href="<?=url('donate?tab=online')?>">Try Online Payment Again</a>
        <?php endif; ?>
        <a class="btn btn-soft" href="<?=url('')?>">Return Home</a>
      </div>

      <div class="review-security">
        <span>🔒</span>
        <p>A donation is counted only after verified server-side payment confirmation. Browser redirects alone never mark a payment successful.</p>
      </div>
    </article>
  </div>
</section>
