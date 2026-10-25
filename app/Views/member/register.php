<section class="member-auth-page member-registration-page">
<div class="container member-auth-shell registration-shell-v58">
  <aside class="member-auth-aside">
    <div class="member-auth-aside-inner">
      <span class="member-auth-kicker">97 FOUNDATION MEMBERSHIP</span>
      <h1>Join a community built on service, trust and impact.</h1>
      <p>Create your secure member account. A unique Member ID and QR verification credential are generated automatically.</p>
      <div class="member-auth-benefits">
        <article><span>✓</span><div><b>Unique Member Identity</b><small>Automatic Member ID and secure QR verification.</small></div></article>
        <article><span>✓</span><div><b>Private Member Portal</b><small>Access your profile, messages, events and Foundation updates.</small></div></article>
        <article><span>✓</span><div><b>Administrator Approval</b><small>Your account is activated only after Foundation review.</small></div></article>
      </div>
      <div class="member-auth-switch"><span>Already registered?</span><a href="<?=url('member/login')?>" data-spa-link>Member Login →</a></div>
    </div>
  </aside>

  <section class="member-auth-card registration-card-v58">
    <?php $regLogo=setting('foundation','logo_path','');$regLogoEnabled=setting('foundation','logo_enabled','1')==='1'; ?>
    <div class="member-auth-official-brand"><?php if($regLogoEnabled&&$regLogo):?><img src="<?=e(public_upload($regLogo))?>" alt="97 Foundation logo"><?php else:?><span>97</span><?php endif;?><div><b>97 FOUNDATION</b><small>Membership · Polonnaruwa / Thambala</small></div></div>
    <header class="member-auth-card-head">
      <div><span>Secure registration</span><h2>Member Registration</h2><p>Complete the details below to submit your membership application.</p></div>
      <div class="member-auth-seal"><?php $logo=setting('foundation','logo_path','');$logoEnabled=setting('foundation','logo_enabled','1')==='1'; if($logoEnabled&&$logo):?><img src="<?=e(public_upload($logo))?>" alt="97 Foundation logo"><?php else:?>97<?php endif;?></div>
    </header>
    <?php if($s=flash('error')):?><div class="alert danger member-auth-alert"><?=e($s)?></div><?php endif;?>
    <form method="post" enctype="multipart/form-data" action="<?=url('member/register')?>" class="member-registration-form member-form-v58">
      <?=csrf_field()?>
      <div class="member-form-section full">
        <div class="member-form-section-title"><span>01</span><div><b>Personal Information</b><small>Basic identity details used for membership records.</small></div></div>
        <div class="member-form-grid">
          <label><span>Full Name <i>*</i></span><input name="name" value="<?=e($registrationOld['name']??'')?>" required maxlength="120" autocomplete="name" placeholder="Enter your full name"></label>
          <label><span>Date of Birth <i>*</i></span><input type="date" name="date_of_birth" value="<?=e($registrationOld['date_of_birth']??'')?>" required></label>
          <label><span>NIC / National ID <i>*</i></span><input name="nic_number" value="<?=e($registrationOld['nic_number']??'')?>" required maxlength="80" autocomplete="off" placeholder="Enter NIC / National ID number"></label>
          <label><span>Sex / Gender <i>*</i></span><select name="gender" required><option value="">Select gender</option><option value="male" <?=($registrationOld['gender']??'')==='male'?'selected':''?>>Male</option><option value="female" <?=($registrationOld['gender']??'')==='female'?'selected':''?>>Female</option><option value="other" <?=($registrationOld['gender']??'')==='other'?'selected':''?>>Other</option><option value="prefer_not_to_say" <?=($registrationOld['gender']??'')==='prefer_not_to_say'?'selected':''?>>Prefer not to say</option></select></label>
          <label><span>Contact Number <i>*</i></span><input name="mobile" value="<?=e($registrationOld['mobile']??'')?>" required maxlength="60" autocomplete="tel" placeholder="e.g. +94 77 123 4567"></label>
          <label class="member-file-field"><span>Profile Picture</span><input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp"><small>Square JPG, PNG or WEBP · max 5 MB.</small></label>
        </div>
      </div>

      <div class="member-form-section full">
        <div class="member-form-section-title"><span>02</span><div><b>Account Information</b><small>Choose the credentials you will use for the Member Portal.</small></div></div>
        <div class="member-form-grid">
          <label><span>Username <i>*</i></span><input name="username" value="<?=e($registrationOld['username']??'')?>" required minlength="4" maxlength="80" pattern="[A-Za-z0-9._-]{4,80}" autocomplete="username" placeholder="Choose a username"><small>Letters, numbers, dot, underscore and hyphen only.</small></label>
          <label><span>Email Address <i>*</i></span><input type="email" name="email" value="<?=e($registrationOld['email']??'')?>" required maxlength="190" autocomplete="email" placeholder="name@example.com"></label>
          <label class="password-field"><span>Password <i>*</i></span><div><input type="password" name="password" required minlength="8" maxlength="12" autocomplete="new-password" placeholder="8–12 characters"><button type="button" data-member-password-toggle aria-label="Show password">Show</button></div><small>Use 8–12 characters. Passwords are securely hashed and never displayed.</small></label>
          <label class="password-field"><span>Confirm Password <i>*</i></span><div><input type="password" name="password_confirmation" required minlength="8" maxlength="12" autocomplete="new-password" placeholder="Repeat your password"><button type="button" data-member-password-toggle aria-label="Show password confirmation">Show</button></div></label>
        </div>
      </div>

      <div class="member-form-section full member-security-section">
        <div class="member-form-section-title"><span>03</span><div><b>Security Check</b><small>Enter the four-digit number to protect the registration form.</small></div></div>
        <div class="member-captcha-v58">
          <div><small>Security Number</small><strong><?=e($captcha)?></strong><span>Enter exactly as shown.</span></div>
          <label><span>Enter Number <i>*</i></span><input name="captcha" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required autocomplete="off" placeholder="0000"></label>
        </div>
        <div class="member-security-note"><span>🔒</span><div><b>Secure membership registration</b><small>Passwords are protected using Argon2id. Private identity information is never displayed in the public member directory.</small></div></div>
      </div>

      <div class="member-form-actions full"><a class="btn btn-outline member-cancel-action" href="<?=url('members')?>">Cancel</a><button class="btn btn-primary btn-large" type="submit">Submit Membership Registration →</button></div>
    </form>
  </section>
</div>
</section>