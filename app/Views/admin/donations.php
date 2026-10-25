<?php
$icon=function(string $name):string{$m=[
'all'=>'<svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="13" rx="3"/><path d="M8 6V4h8v2M8 11h8M8 15h5"/></svg>',
'pending'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
'approved'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.3 2.4 4.8-5"/></svg>',
'rejected'=>'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/></svg>',
'view'=>'<svg viewBox="0 0 24 24"><path d="M2.8 12s3.2-5 9.2-5 9.2 5 9.2 5-3.2 5-9.2 5-9.2-5-9.2-5Z"/><circle cx="12" cy="12" r="2.4"/></svg>',
'edit'=>'<svg viewBox="0 0 24 24"><path d="m4 20 4.2-1 10.9-10.9-3.2-3.2L5 15.8 4 20Z"/><path d="m14.8 6 3.2 3.2"/></svg>',
'delete'=>'<svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5m4-5v5"/></svg>',
'filter'=>'<svg viewBox="0 0 24 24"><path d="M4 6h16M7 12h10m-7 6h4"/></svg>'
];return $m[$name]??$m['all'];};
?>
<div class="page-actions donation-page-head">
  <div><h2>Donation Verification</h2><p class="page-breadcrumbs"><span>Dashboard</span><i>›</i><span>Fundraising</span><i>›</i><span>Donation Verification</span></p></div>
  <?php if(\App\Core\Auth::can('approve_donations')):?><a class="admin-btn donation-add-btn" href="<?=url('admin/donations/create')?>"><span>＋</span> Add Donation</a><?php endif;?>
</div>
<div class="donation-page-intro"><p>Review donation records, verify payment evidence and manage the full donation lifecycle. Only approved donations contribute to public campaign totals.</p></div>
<div class="donation-queue-stats donation-stats-premium">
  <a class="admin-panel <?=($status??'')===''?'selected':''?>" href="<?=url('admin/donations')?>"><span class="donation-stat-icon all"><?=$icon('all')?></span><div><span>All Donations</span><b><?=number_format((int)($counts['total']??0))?></b><small>Total donation records</small></div></a>
  <a class="admin-panel pending <?=($status??'')==='pending_verification'?'selected':''?>" href="<?=url('admin/donations?status=pending_verification')?>"><span class="donation-stat-icon pending"><?=$icon('pending')?></span><div><span>Pending Verification</span><b><?=number_format((int)($counts['pending']??0))?></b><small>Awaiting review</small></div></a>
  <a class="admin-panel approved <?=($status??'')==='approved'?'selected':''?>" href="<?=url('admin/donations?status=approved')?>"><span class="donation-stat-icon approved"><?=$icon('approved')?></span><div><span>Verified</span><b><?=number_format((int)($counts['approved']??0))?></b><small>Successfully approved</small></div></a>
  <a class="admin-panel rejected <?=($status??'')==='rejected'?'selected':''?>" href="<?=url('admin/donations?status=rejected')?>"><span class="donation-stat-icon rejected"><?=$icon('rejected')?></span><div><span>Rejected</span><b><?=number_format((int)($counts['rejected']??0))?></b><small>Rejected donations</small></div></a>
</div>
<section class="admin-panel donation-management-card">
  <form method="get" action="<?=url('admin/donations')?>" class="donation-filter-bar">
    <label class="donation-search-box"><span>⌕</span><input name="q" value="<?=e($q??'')?>" placeholder="Search by donor, email, reference, transaction ID…"></label>
    <label><span>Status</span><select name="status"><option value="">All Statuses</option><option value="pending_verification" <?=($status??'')==='pending_verification'?'selected':''?>>Pending Verification</option><option value="approved" <?=($status??'')==='approved'?'selected':''?>>Verified</option><option value="rejected" <?=($status??'')==='rejected'?'selected':''?>>Rejected</option><option value="refunded" <?=($status??'')==='refunded'?'selected':''?>>Refunded</option><option value="cancelled" <?=($status??'')==='cancelled'?'selected':''?>>Cancelled</option></select></label>
    <label><span>From</span><input type="date" name="from" value="<?=e($from??'')?>"></label>
    <label><span>To</span><input type="date" name="to" value="<?=e($to??'')?>"></label>
    <button class="outline-link donation-filter-btn"><?=$icon('filter')?> <span>Filter</span></button>
    <?php if(($q??'')!==''||($status??'')!==''||($from??'')!==''||($to??'')!==''):?><a class="donation-clear-filter" href="<?=url('admin/donations')?>">Clear Filters</a><?php endif;?>
  </form>
  <div class="table-wrap donation-table-wrap"><table class="donation-professional-table"><thead><tr><th>#</th><th>Donation ID</th><th>Donor Details</th><th>Transaction ID</th><th>Amount</th><th>Date & Time</th><th>Status</th><th>Payment Method</th><th>Actions</th></tr></thead><tbody>
  <?php $rowNo=(int)(($pagination['offset']??0)+1);foreach($donations as $d):?>
    <tr class="<?=$d['status']==='pending_verification'?'pending-row':''?>"><td><?=number_format($rowNo++)?></td><td><b class="donation-ref"><?=e(substr($d['public_id'],0,18))?></b><small>Ref: <?=e(substr($d['public_id'],-8))?></small></td><td><b><?=e($d['is_anonymous']?'Anonymous':$d['donor_name'])?></b><small><?=e($d['donor_email']??'')?></small><small><?=e($d['donor_phone']??'')?></small></td><td><b><?=e($d['transaction_reference']?:'—')?></b><small><?=e($d['campaign_title']?:'General Foundation Fund')?></small></td><td><b><?=money($d['amount'],$d['currency'])?></b></td><td><b><?=e(date('d M Y',strtotime($d['created_at'])))?></b><small><?=e(date('h:i A',strtotime($d['created_at'])))?></small></td><td><span class="status-badge <?=e($d['status'])?>"><?=e($d['status']==='approved'?'Verified':ucwords(str_replace('_',' ',$d['status'])))?></span></td><td><b><?=e(ucwords(str_replace('_',' ',$d['payment_method'])))?></b><small><?=e($d['account_title']?:($d['account_bank']?:'Direct record'))?></small></td><td><div class="donation-row-actions"><a class="icon-action" href="<?=url('admin/donations/view/'.$d['id'])?>" title="View donation"><?=$icon('view')?></a><?php if(!empty($d['proof_path'])):?><a class="icon-action" href="<?=url('admin/donations/proof/'.$d['id'])?>" target="_blank" rel="noopener noreferrer" title="Open receipt / proof">↗</a><?php endif;?><?php if(\App\Core\Auth::can('approve_donations')):?><a class="icon-action" href="<?=url('admin/donations/edit/'.$d['id'])?>" title="Edit donation"><?=$icon('edit')?></a><?php endif;?><?php if(($user['role']??'')==='super_admin'):?><form method="post" action="<?=url('admin/donations/delete')?>" data-confirm="Permanently delete this donation record? This action cannot be undone."><?=csrf_field()?><input type="hidden" name="id" value="<?=$d['id']?>"><button class="icon-action danger" title="Delete donation"><?=$icon('delete')?></button></form><?php endif;?></div></td></tr>
  <?php endforeach;?><?php if(!$donations):?><tr><td colspan="9" class="empty">No donation records match the selected filters.</td></tr><?php endif;?>
  </tbody></table></div>
  <?=admin_pagination($pagination??[])?>
  <div class="donation-action-legend"><span><?=$icon('view')?><b>View</b><small>Donation details</small></span><span><?=$icon('edit')?><b>Edit</b><small>Update donation information</small></span><span><?=$icon('approved')?><b>Verify / Reject</b><small>Update verification status</small></span><span><?=$icon('delete')?><b>Delete</b><small>Super Admin only</small></span></div>
</section>
