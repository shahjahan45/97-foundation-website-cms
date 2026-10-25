<?php $logo=setting('foundation','logo_path','');$logoEnabled=setting('foundation','logo_enabled','1')==='1';$memberImage=setting('foundation','member_login_image_path','');$memberBg=$memberImage?public_upload($memberImage):(setting('foundation','portal_login_image_path','')?public_upload(setting('foundation','portal_login_image_path','')):asset('images/demo/hero-children.jpg'));?>
<section class="member-auth-page member-login-page">
<div class="container member-login-layout-v58">
  <aside class="member-login-visual-v58" style="background-image:linear-gradient(135deg,rgba(0,52,43,.93),rgba(5,116,67,.70)),url('<?=e($memberBg)?>')">
    <div class="member-login-visual-copy">
      <span class="member-auth-kicker">97 FOUNDATION MEMBER PORTAL</span>
      <h1>Welcome back.</h1>
      <p>Access your verified Member ID, QR credential, Foundation announcements, events, messages and profile.</p>
      <div class="member-login-feature-row"><span>✓ Secure portal</span><span>✓ Digital Member ID</span><span>✓ Foundation updates</span></div>
    </div>
  </aside>
  <section class="member-auth-card member-login-card-v58">
    <div class="member-auth-official-brand"><?php if($logoEnabled&&$logo):?><img src="<?=e(public_upload($logo))?>" alt="97 Foundation logo"><?php else:?><span>97</span><?php endif;?><div><b>97 FOUNDATION</b><small>Official Member Portal · Sri Lanka</small></div></div>
    <header class="member-auth-card-head compact"><div><span>Member access</span><h2>Sign in to your account</h2><p>Use your registered username or email address.</p></div><div class="member-auth-seal"><?php if($logoEnabled&&$logo):?><img src="<?=e(public_upload($logo))?>" alt="97 Foundation logo"><?php else:?>97<?php endif;?></div></header>
    <?php if($s=flash('error')):?><div class="alert danger member-auth-alert"><?=e($s)?></div><?php endif;?>
    <form method="post" action="<?=url('member/login')?>" class="member-login-form-v58">
      <?=csrf_field()?>
      <label><span>Username or Email</span><input name="identifier" required autocomplete="username" placeholder="Username or email address"></label>
      <label class="password-field"><span>Password</span><div><input type="password" name="password" required autocomplete="current-password" placeholder="Enter your password"><button type="button" data-member-password-toggle aria-label="Show password">Show</button></div></label>
      <div class="member-captcha-v58 compact-captcha"><div><small>Security Number</small><strong><?=e($captcha)?></strong><span>Enter the 4-digit number.</span></div><label><span>Enter Number</span><input name="captcha" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required autocomplete="off" placeholder="0000"></label></div>
      <button class="btn btn-primary btn-large full member-login-submit">Sign In to Member Portal →</button>
    </form>
    <div class="member-login-footer-v58"><span>New to 97 Foundation?</span><a href="<?=url('member/register')?>" data-no-spa>Create a member account</a><a href="<?=url('contact')?>">Need help?</a></div>
  </section>
</div>
</section>
