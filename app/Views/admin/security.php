<?php $adminTitle='Security & Two-Factor Authentication'; ?>
<div class="page-actions"><div><h2>Security Centre</h2><p>Protect administrator access with numeric CAPTCHA, login throttling, secure sessions and optional authenticator-based 2FA.</p></div><span class="security-score">SECURE WORKSPACE</span></div>
<div class="security-grid">
<section class="admin-panel security-card"><div class="panel-head"><div><h3>Two-Factor Authentication</h3><p>Add a second authentication factor to this administrator account.</p></div><span class="status-badge <?=$user['two_factor_enabled']?'active':'inactive'?>"><?=$user['two_factor_enabled']?'Enabled':'Disabled'?></span></div>
<?php if(!$user['two_factor_enabled']):?>
<div class="security-visual"><span>⌾</span><div><b>Authenticator Protection</b><small>A 6-digit time-based code is required after your password when 2FA is enabled.</small></div></div>
<?php if(!$pendingSecret):?><form method="post" action="<?=url('admin/security/2fa')?>"><?=csrf_field()?><input type="hidden" name="action" value="generate"><button class="admin-btn">Generate Authenticator Secret</button></form>
<?php else:?><div class="secret-box"><small>Authenticator Secret</small><code><?=e($pendingSecret)?></code><p>Add this key manually to an authenticator app, then enter the current 6-digit code below.</p></div><form method="post" action="<?=url('admin/security/2fa')?>" class="admin-form security-form"><?=csrf_field()?><input type="hidden" name="action" value="enable"><label class="full"><span>6-digit Authenticator Code</span><input name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required placeholder="000000"></label><button class="admin-btn full">Confirm & Enable 2FA</button></form><?php endif;?>
<?php else:?><div class="security-visual enabled"><span>✓</span><div><b>2FA is active</b><small>Your administrator account requires an authenticator code after password verification.</small></div></div><form method="post" action="<?=url('admin/security/2fa')?>" data-confirm="Disable two-factor authentication for this account?"><?=csrf_field()?><input type="hidden" name="action" value="disable"><button class="danger-link">Disable 2FA</button></form><?php endif;?>
</section>
<section class="admin-panel security-card"><div class="panel-head"><div><h3>Security Controls Included</h3><p>Core protections enabled in this package.</p></div></div><div class="security-control-grid">
<?php foreach([
['CSRF protection','All state-changing forms use server-verified tokens.'],
['XSS output escaping','Dynamic output is HTML-escaped by default.'],
['Prepared database queries','PDO prepared statements protect query parameters.'],
['Login throttling','Failed attempts are rate-limited by identity and IP.'],
['Numeric login CAPTCHA','A fresh 4-digit security number is generated for login.'],
['Secure cookies','HttpOnly and SameSite cookies; Secure flag is used on HTTPS.'],
['Private donation proofs','Payment evidence is stored outside the public web directory.'],
['Upload MIME validation','Images and proof files are validated from server-detected MIME types.'],
['Security headers','CSP, frame denial, no-sniff, referrer and permissions policies.'],
['Audit logging','Important administrator and workflow actions are recorded.']
] as [$title,$text]):?><div><span>✓</span><b><?=e($title)?></b><small><?=e($text)?></small></div><?php endforeach;?></div></section>
</div>
