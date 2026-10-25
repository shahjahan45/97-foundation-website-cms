<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Site;
use App\Core\Officials;

final class HomeController extends Controller
{
    public function index(): void
    {
        $sectionRows=Database::all('SELECT * FROM content_sections WHERE section_key LIKE "home_%" ORDER BY sort_order,id');$sections=[];foreach($sectionRows as $row)$sections[$row['section_key']]=$row;
        $limit=function(string $key,int $default,int $max)use($sections):int{$v=(int)($sections[$key]['item_limit']??$default);return max(1,min($max,$v));};
        $campaignLimit=$limit('home_campaigns',3,6);$causeLimit=$limit('home_causes',4,8);$galleryLimit=$limit('home_gallery',6,12);$eventLimit=$limit('home_events',3,6);$newsLimit=$limit('home_news',3,6);
        $campaigns=Database::all('SELECT c.*, COALESCE(SUM(CASE WHEN d.status="approved" THEN d.amount ELSE 0 END),0) raised FROM campaigns c LEFT JOIN donations d ON d.campaign_id=c.id WHERE c.status="active" AND c.is_active=1 AND c.is_visible=1 GROUP BY c.id ORDER BY c.created_at DESC LIMIT '.$campaignLimit);
        $siteSettings=Site::settings();$hero=hero_settings($siteSettings);$heroSlides=hero_background_slides($hero);
        $categories=Database::all('SELECT * FROM campaign_categories WHERE is_active=1 ORDER BY sort_order,id LIMIT '.$causeLimit);
        $gallery=Database::all('SELECT * FROM gallery_items WHERE is_active=1 ORDER BY sort_order,id DESC LIMIT '.$galleryLimit);
        $events=Database::all('SELECT * FROM events WHERE is_active=1 AND status="published" ORDER BY (event_date>=CURDATE()) DESC, CASE WHEN event_date>=CURDATE() THEN event_date END ASC, event_date DESC, id DESC LIMIT '.$eventLimit);
        $posts=Database::all('SELECT * FROM posts WHERE status="published" AND published_at<=NOW() ORDER BY is_featured DESC,published_at DESC,id DESC LIMIT '.$newsLimit);
        $stats=[
            'campaigns'=>(int)(Database::one('SELECT COUNT(*) c FROM campaigns WHERE status="active" AND is_active=1 AND is_visible=1')['c']??0),
            'volunteers'=>(int)(Database::one('SELECT COUNT(*) c FROM volunteers WHERE status IN("approved","active")')['c']??0),
            'verified_donations'=>(int)(Database::one('SELECT COUNT(*) c FROM donations WHERE status="approved"')['c']??0),
            'focus_areas'=>(int)(Database::one('SELECT COUNT(*) c FROM campaign_categories WHERE is_active=1')['c']??0),
        ];
        $this->view('home',['campaigns'=>$campaigns,'hero'=>$hero,'heroSlides'=>$heroSlides,'heroPreloadUrl'=>$heroSlides[0]['desktop']??'','categories'=>$categories,'gallery'=>$gallery,'events'=>$events,'posts'=>$posts,'sections'=>$sections,'stats'=>$stats,'settings'=>$siteSettings,'seo'=>['title'=>setting('seo','default_title','97 Foundation — Community Support in Sri Lanka'),'description'=>setting('seo','default_description','97 Foundation is a community support and charity platform serving Sri Lanka, with a focus on Polonnaruwa and Thambala.')]]);
    }
    public function about(): void { $this->pageView('about'); }

    /** About Us → What We Do (V7.1 navigation). */
    public function whatWeDo(): void
    {
        $page = Site::page('what-we-do') ?: [
            'slug' => 'what-we-do', 'title' => 'What We Do', 'eyebrow' => 'WHAT WE DO', 'hero_title' => 'What We Do',
            'hero_subtitle' => 'Education, family support, student empowerment and social work for communities across Polonnaruwa and Thambala.',
            'body' => '97 Foundation delivers practical, accountable support through four connected programme areas: education support, family support, student empowerment and social work. Every programme is planned with local volunteers, reviewed by Foundation officials and reported transparently.',
            'image_path' => 'demo/cause-education.jpg', 'meta_title' => 'What We Do | 97 Foundation',
            'meta_description' => 'Discover the programmes and community support delivered by 97 Foundation in Sri Lanka.', 'answer_summary' => '',
        ];
        $categories = Database::all('SELECT * FROM campaign_categories WHERE is_active=1 ORDER BY sort_order,id');
        $this->view('what-we-do', ['page' => $page, 'categories' => $categories, 'settings' => Site::settings(), 'seo' => $this->seo($page)]);
    }

    /** Members → Officials (current office bearers). */
    public function officials(): void
    {
        $this->view('officials', [
            'mode' => 'current', 'officials' => Officials::published('current'), 'settings' => Site::settings(),
            'seo' => ['title' => 'Foundation Officials | 97 Foundation', 'description' => 'Meet the current office bearers who lead 97 Foundation.'],
        ]);
    }

    /** Members → Past Officials (grouped by service term). */
    public function pastOfficials(): void
    {
        $rows = Officials::published('past');
        $this->view('officials', [
            'mode' => 'past', 'officials' => $rows, 'groups' => Officials::groupedByTerm($rows), 'settings' => Site::settings(),
            'seo' => ['title' => 'Past Officials | 97 Foundation', 'description' => 'Honouring the past office bearers who served 97 Foundation.'],
        ]);
    }
    public function events(): void
    {
        $page=Site::page('events'); $events=Database::all('SELECT * FROM events WHERE is_active=1 AND status IN("published","completed") ORDER BY event_date DESC');
        $this->view('events',['page'=>$page,'events'=>$events,'settings'=>Site::settings(),'seo'=>$this->seo($page)]);
    }
    public function gallery(): void
    {
        $page=Site::page('gallery'); $items=Database::all('SELECT * FROM gallery_items WHERE is_active=1 ORDER BY sort_order,id DESC');
        $this->view('gallery',['page'=>$page,'items'=>$items,'settings'=>Site::settings(),'seo'=>$this->seo($page)]);
    }

    public function members(): void
    {
        $page=Site::page('members');
        $members=Database::all('SELECT u.name,u.profile_photo,m.member_number,m.joined_at,m.role_title,m.bio,m.verification_token FROM member_profiles m JOIN users u ON u.id=m.user_id WHERE u.role="member" AND u.status="active" AND m.is_public=1 ORDER BY m.joined_at,u.name');
        $this->view('members',['page'=>$page,'members'=>$members,'settings'=>Site::settings(),'seo'=>['title'=>'Foundation Members | 97 Foundation','description'=>'Verified public directory of 97 Foundation members.']]);
    }
    public function causes(): void
    {
        $page=Site::page('causes');
        $categories=Database::all('SELECT * FROM campaign_categories WHERE is_active=1 ORDER BY sort_order,id');
        $this->view('causes',['page'=>$page,'categories'=>$categories,'settings'=>Site::settings(),'seo'=>$this->seo($page)]);
    }
    public function campaigns(): void
    {
        $page=Site::page('campaigns');
        $campaigns=Database::all('SELECT c.*,COALESCE(SUM(CASE WHEN d.status="approved" THEN d.amount ELSE 0 END),0) raised FROM campaigns c LEFT JOIN donations d ON d.campaign_id=c.id WHERE c.is_active=1 AND c.is_visible=1 AND c.status IN("active","completed","upcoming") GROUP BY c.id ORDER BY FIELD(c.status,"active","upcoming","completed"),c.created_at DESC');
        $this->view('campaigns',['page'=>$page,'campaigns'=>$campaigns,'settings'=>Site::settings(),'seo'=>$this->seo($page)]);
    }
    public function campaign(string $slug): void
    {
        $c=Database::one('SELECT c.*,COALESCE(SUM(CASE WHEN d.status="approved" THEN d.amount ELSE 0 END),0) raised FROM campaigns c LEFT JOIN donations d ON d.campaign_id=c.id WHERE c.slug=? AND c.is_active=1 AND c.is_visible=1 GROUP BY c.id LIMIT 1',[$slug]);
        if(!$c){http_response_code(404);$this->view('404',['settings'=>Site::settings()]);return;}
        $this->view('campaign-detail',['campaign'=>$c,'settings'=>Site::settings(),'seo'=>['title'=>$c['title'].' | 97 Foundation','description'=>$c['short_description']?:'Support this 97 Foundation campaign.']]);
    }
    public function contactPage(): void { $this->pageView('contact'); }
    private function pageView(string $slug): void
    {
        $page=Site::page($slug); if(!$page){http_response_code(404);$this->view('404',['settings'=>Site::settings()]);return;}
        $this->view('page',['page'=>$page,'settings'=>Site::settings(),'seo'=>$this->seo($page)]);
    }
    private function seo(?array $page): array { return ['title'=>$page['meta_title']??($page['title']??setting('seo','default_title','97 Foundation')),'description'=>$page['meta_description']??setting('seo','default_description','')]; }

    public function dynamicPage(string $slug): void
    {
        if(!preg_match('/^[a-z0-9-]+$/',$slug)){http_response_code(404);$this->view('404',['settings'=>Site::settings()]);return;}
        $this->pageView($slug);
    }
    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $urls=['','about','what-we-do','causes','campaigns','events','gallery','members','members/officials','members/past-officials','news','volunteer','donate','contact'];
        $cmsPages=Database::all('SELECT slug FROM pages WHERE is_active=1'); foreach($cmsPages as $p)$urls[]=(string)$p['slug'];
        $campaigns=Database::all('SELECT slug FROM campaigns WHERE is_active=1 AND is_visible=1 AND status<>"draft"'); foreach($campaigns as $c)$urls[]='campaigns/'.$c['slug'];
        $posts=Database::all('SELECT slug FROM posts WHERE status="published" AND published_at<=NOW()'); foreach($posts as $p)$urls[]='news/'.$p['slug'];
        $urls=array_values(array_unique($urls));
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($urls as $u) echo '<url><loc>'.e(absolute_url($u)).'</loc></url>'; echo '</urlset>'; exit;
    }
    public function robots(): void { header('Content-Type: text/plain; charset=UTF-8'); echo "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /portal/\nDisallow: /login\nSitemap: ".absolute_url('sitemap.xml')."\n"; exit; }
    public function favicon(): void
    {
        $icon = foundation_favicon_url();
        header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');
        if ($icon !== '') {
            header('Location: ' . $icon, true, 302);
            exit;
        }
        header('Content-Type: image/svg+xml; charset=UTF-8');
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="18" fill="#e8f7ee"/><text x="32" y="40" text-anchor="middle" font-family="Arial,sans-serif" font-size="26" font-weight="900" fill="#087d48">97</text></svg>';
        exit;
    }
}
