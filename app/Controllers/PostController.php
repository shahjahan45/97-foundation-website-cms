<?php
namespace App\Controllers;
use App\Core\Controller;use App\Core\Database;use App\Core\Site;

/** Public News & Blog (V7.2): categories, search, pagination, article pages with related posts. */
final class PostController extends Controller
{
    private const PER_PAGE = 12;

    private static function categories(): array
    {
        try { return Database::all('SELECT pc.id,pc.name,pc.slug,pc.description,COUNT(p.id) posts FROM post_categories pc LEFT JOIN posts p ON p.category_id=pc.id AND p.status="published" AND p.published_at<=NOW() WHERE pc.is_active=1 GROUP BY pc.id,pc.name,pc.slug,pc.description,pc.sort_order ORDER BY pc.sort_order,pc.name'); }
        catch (\Throwable) { return []; }
    }

    public function index(): void { $this->listing(null); }

    public function category(string $slug): void
    {
        $cat = null;
        try { $cat = Database::one('SELECT * FROM post_categories WHERE slug=? AND is_active=1 LIMIT 1', [$slug]); } catch (\Throwable) {}
        if (!$cat) { http_response_code(404); $this->view('404', ['settings' => Site::settings()]); return; }
        $this->listing($cat);
    }

    private function listing(?array $category): void
    {
        $type = in_array($_GET['type'] ?? '', ['news', 'blog'], true) ? $_GET['type'] : '';
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $where = 'p.status="published" AND p.published_at<=NOW()';
        $params = [];
        if ($type) { $where .= ' AND p.post_type=?'; $params[] = $type; }
        if ($category) { $where .= ' AND p.category_id=?'; $params[] = (int)$category['id']; }
        if ($q !== '') { $like = '%' . $q . '%'; $where .= ' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)'; array_push($params, $like, $like, $like); }
        $join = self::categoryJoin();
        $total = (int)(Database::one('SELECT COUNT(*) c FROM posts p WHERE ' . $where, $params)['c'] ?? 0);
        $pages = max(1, (int)ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $rows = Database::all('SELECT p.*' . $join['cols'] . ' FROM posts p' . $join['sql'] . ' WHERE ' . $where . ' ORDER BY p.is_featured DESC,p.published_at DESC,p.id DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), $params);
        $featured = null;
        if ($page === 1 && $q === '' && $rows) $featured = array_shift($rows);

        $base = $category ? 'news/category/' . $category['slug'] : 'news';
        $title = $category ? $category['name'] . ' | News & Blog | 97 Foundation' : ($type === 'news' ? 'News | 97 Foundation' : ($type === 'blog' ? 'Blog | 97 Foundation' : 'News & Blog | 97 Foundation'));
        $desc = $category && trim((string)$category['description']) !== '' ? $category['description'] : 'Latest news, community stories and updates from 97 Foundation in Thambala, Polonnaruwa, Sri Lanka.';
        $canonicalQuery = array_filter(['type' => $type, 'page' => $page > 1 ? $page : null]);
        $crumbs = [['Home', ''], ['News & Blog', 'news']];
        if ($category) $crumbs[] = [$category['name'], $base];
        $this->view('posts', [
            'posts' => $rows, 'featured' => $featured, 'type' => $type, 'q' => $q, 'category' => $category, 'categories' => self::categories(),
            'page' => $page, 'pages' => $pages, 'total' => $total, 'base' => $base, 'settings' => Site::settings(),
            'seo' => ['title' => $title, 'description' => $desc, 'canonical' => absolute_url($base) . ($canonicalQuery ? '?' . http_build_query($canonicalQuery) : ''), 'noindex' => $q !== '', 'breadcrumbs' => $crumbs],
        ]);
    }

    public function show(string $slug): void
    {
        $join = self::categoryJoin();
        $p = Database::one('SELECT p.*' . $join['cols'] . ' FROM posts p' . $join['sql'] . ' WHERE p.slug=? AND p.status="published" AND p.published_at<=NOW() LIMIT 1', [$slug]);
        if (!$p) { http_response_code(404); $this->view('404', ['settings' => Site::settings()]); return; }
        $related = [];
        try {
            if (!empty($p['category_id'])) $related = Database::all('SELECT p.*' . $join['cols'] . ' FROM posts p' . $join['sql'] . ' WHERE p.status="published" AND p.published_at<=NOW() AND p.id<>? AND p.category_id=? ORDER BY p.published_at DESC LIMIT 3', [(int)$p['id'], (int)$p['category_id']]);
            if (count($related) < 3) {
                $ids = array_merge([(int)$p['id']], array_map(static fn($r) => (int)$r['id'], $related));
                $more = Database::all('SELECT p.*' . $join['cols'] . ' FROM posts p' . $join['sql'] . ' WHERE p.status="published" AND p.published_at<=NOW() AND p.id NOT IN (' . implode(',', $ids) . ') ORDER BY p.post_type=? DESC,p.published_at DESC LIMIT ' . (3 - count($related)), [$p['post_type']]);
                $related = array_merge($related, $more);
            }
        } catch (\Throwable) {}
        $article = article_render($p['content'] ?? '');
        $crumbs = [['Home', ''], ['News & Blog', 'news']];
        if (!empty($p['category_slug'])) $crumbs[] = [$p['category_name'], 'news/category/' . $p['category_slug']];
        $crumbs[] = [$p['title'], 'news/' . $p['slug']];
        $this->view('post-detail', [
            'post' => $p, 'article' => $article, 'related' => $related, 'settings' => Site::settings(),
            'seo' => [
                'title' => ($p['meta_title'] ?: $p['title']) . (str_contains((string)$p['meta_title'], '97 Foundation') ? '' : ' | 97 Foundation'),
                'description' => $p['meta_description'] ?: ($p['excerpt'] ?: mb_strimwidth(trim(strip_tags((string)$p['content'])), 0, 160, '…')),
                'image' => $p['featured_image'] ?? '', 'image_alt' => $p['title'], 'type' => 'article',
                'published' => $p['published_at'], 'modified' => $p['updated_at'] ?? $p['published_at'], 'section' => $p['category_name'] ?? ucfirst((string)$p['post_type']),
                'canonical' => absolute_url('news/' . $p['slug']), 'breadcrumbs' => $crumbs,
            ],
        ]);
    }

    /** Category columns when the V7.2 update is installed; harmless fallback otherwise. */
    private static function categoryJoin(): array
    {
        static $join = null;
        if ($join !== null) return $join;
        try { Database::one('SELECT category_id FROM posts LIMIT 1'); Database::one('SELECT id FROM post_categories LIMIT 1'); return $join = ['cols' => ',pc.name category_name,pc.slug category_slug', 'sql' => ' LEFT JOIN post_categories pc ON pc.id=p.category_id AND pc.is_active=1']; }
        catch (\Throwable) { return $join = ['cols' => ',NULL category_name,NULL category_slug', 'sql' => '']; }
    }
}
