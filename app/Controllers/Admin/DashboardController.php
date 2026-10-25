<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;
final class DashboardController extends Controller
{
    public function index(): void
    {
        $u=Auth::requireAdmin();
        $stats=[
            'approved'=>(float)(Database::one('SELECT COALESCE(SUM(amount),0) total FROM donations WHERE status="approved"')['total']??0),
            'pending'=>(float)(Database::one('SELECT COALESCE(SUM(amount),0) total FROM donations WHERE status="pending_verification"')['total']??0),
            'campaigns'=>(int)(Database::one('SELECT COUNT(*) c FROM campaigns WHERE status="active" AND is_active=1 AND is_visible=1')['c']??0),
            'volunteers'=>(int)(Database::one('SELECT COUNT(*) c FROM volunteers WHERE status IN("approved","active")')['c']??0),
            'pending_volunteers'=>(int)(Database::one('SELECT COUNT(*) c FROM volunteers WHERE status="pending"')['c']??0),
            'inquiries'=>(int)(Database::one('SELECT COUNT(*) c FROM contact_messages WHERE status IN("new","open","in_progress")')['c']??0),
            'messages'=>(int)(Database::one('SELECT COUNT(*) c FROM messages WHERE recipient_id=? AND read_at IS NULL',[(int)$u['id']])['c']??0),
            'gallery'=>(int)(Database::one('SELECT COUNT(*) c FROM gallery_items WHERE is_active=1')['c']??0),
            'live_chats'=>(int)(Database::one('SELECT COUNT(*) c FROM chat_conversations WHERE status IN("open","waiting")')['c']??0),
            'members'=>(int)(Database::one('SELECT COUNT(*) c FROM users WHERE role="member" AND status="active"')['c']??0),
        ];
        $recent=Database::all('SELECT action,module,description,created_at FROM audit_logs ORDER BY id DESC LIMIT 8');
        $pending=Database::all('SELECT id,full_name,email,created_at FROM volunteers WHERE status="pending" ORDER BY id DESC LIMIT 5');
        try{$onlineStats=Database::one('SELECT COUNT(*) total,COALESCE(SUM(CASE WHEN status="successful" THEN amount ELSE 0 END),0) total_amount,SUM(status="successful") successful,SUM(status IN("pending","processing")) pending,SUM(status="failed") failed,COALESCE(SUM(CASE WHEN DATE(paid_at)=CURDATE() AND status="successful" THEN amount ELSE 0 END),0) today_amount,COALESCE(SUM(CASE WHEN paid_at>=DATE_FORMAT(CURDATE(),"%Y-%m-01") AND status="successful" THEN amount ELSE 0 END),0) month_amount FROM payment_transactions')?:[];$recentOnline=Database::all('SELECT id,public_id,donor_name,amount,status,gateway,created_at FROM payment_transactions ORDER BY id DESC LIMIT 5');}catch(\Throwable){$onlineStats=['total'=>0,'total_amount'=>0,'successful'=>0,'pending'=>0,'failed'=>0,'today_amount'=>0,'month_amount'=>0];$recentOnline=[];}
        $rawChart=Database::all('SELECT DATE_FORMAT(created_at,"%Y-%m") period,SUM(CASE WHEN status="approved" THEN amount ELSE 0 END) approved,SUM(CASE WHEN status="pending_verification" THEN amount ELSE 0 END) pending FROM donations WHERE created_at>=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),"%Y-%m-01") GROUP BY period ORDER BY period');
        $chartMap=[];foreach($rawChart as $row)$chartMap[$row['period']]=$row;$donationChart=[];$maxChart=0.0;for($i=5;$i>=0;$i--){$dt=(new \DateTimeImmutable('first day of this month'))->modify('-'.$i.' months');$key=$dt->format('Y-m');$approved=(float)($chartMap[$key]['approved']??0);$pendingAmount=(float)($chartMap[$key]['pending']??0);$maxChart=max($maxChart,$approved,$pendingAmount);$donationChart[]=['label'=>$dt->format('M'),'period'=>$dt->format('M Y'),'approved'=>$approved,'pending'=>$pendingAmount];}
        $this->view('admin/dashboard',['user'=>$u,'stats'=>$stats,'recent'=>$recent,'pending'=>$pending,'donationChart'=>$donationChart,'chartMax'=>$maxChart,'onlineStats'=>$onlineStats,'recentOnline'=>$recentOnline,'adminTitle'=>'Admin Dashboard'],'admin');
    }
}
