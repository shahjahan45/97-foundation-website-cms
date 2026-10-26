<?php
namespace App\Controllers\Admin;
use App\Core\Controller;use App\Core\Auth;use App\Core\Database;use App\Core\Session;use App\Core\Audit;use App\Core\Upload;

/** Admin → Content & Website → News & Blog (V7.2): categories, readable URLs, publish/unpublish, SEO. */
final class PostAdminController extends Controller
{
    private const STATUSES = ['draft', 'published', 'archived'];

    private static function categories(): array
    {
        try { return Database::all('SELECT pc.*,(SELECT COUNT(*) FROM posts p WHERE p.category_id=pc.id) posts FROM post_categories pc ORDER BY pc.sort_order,pc.name'); }
        catch (\Throwable) { return []; }
    }

    public function index(): void
    {
        $u = Auth::requireAdmin(); Auth::enforce('manage_content');
        $type = in_array($_GET['type'] ?? '', ['news', 'blog'], true) ? $_GET['type'] : '';
        $state = in_array($_GET['state'] ?? '', ['published', 'scheduled', 'draft', 'archived'], true) ? $_GET['state'] : '';
        $cat = (int)($_GET['category'] ?? 0);
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
        $where = '1=1'; $params = [];
        if ($type) { $where .= ' AND p.post_type=?'; $params[] = $type; }
        if ($state === 'published') $where .= ' AND p.status="published" AND p.published_at<=NOW()';
        elseif ($state === 'scheduled') $where .= ' AND p.status="published" AND p.published_at>NOW()';
        elseif ($state) { $where .= ' AND p.status=?'; $params[] = $state; }
        $cats = self::categories();
        if ($cat && $cats) { $where .= ' AND p.category_id=?'; $params[] = $cat; }
        if ($q !== '') { $like = '%' . $q . '%'; $where .= ' AND (p.title LIKE ? OR p.slug LIKE ? OR p.excerpt LIKE ?)'; array_push($params, $like, $like, $like); }
        $join = $cats ? ['cols' => ',pc.name category_name', 'sql' => ' LEFT JOIN post_categories pc ON pc.id=p.category_id'] : ['cols' => ',NULL category_name', 'sql' => ''];
        $total = (int)(Database::one('SELECT COUNT(*) c FROM posts p WHERE ' . $where, $params)['c'] ?? 0);
        $pg = admin_pager($total, 15);
        $rows = Database::all('SELECT p.*' . $join['cols'] . ' FROM posts p' . $join['sql'] . ' WHERE ' . $where . ' ORDER BY COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT ' . (int)$pg['per_page'] . ' OFFSET ' . (int)$pg['offset'], $params);
        $stats = Database::one('SELECT SUM(CASE WHEN status="published" AND published_at<=NOW() THEN 1 ELSE 0 END) published,SUM(CASE WHEN status="published" AND published_at>NOW() THEN 1 ELSE 0 END) scheduled,SUM(CASE WHEN status="draft" THEN 1 ELSE 0 END) drafts,SUM(CASE WHEN status="archived" THEN 1 ELSE 0 END) archived FROM posts') ?: [];
        $this->view('admin/posts', ['user' => $u, 'posts' => $rows, 'type' => $type, 'state' => $state, 'categoryFilter' => $cat, 'q' => $q, 'categories' => $cats, 'stats' => $stats, 'pagination' => $pg, 'adminTitle' => 'News & Blog'], 'admin');
    }

    /** Make a slug unique among posts (adds -2, -3 … when needed). */
    private static function uniqueSlug(string $slug, int $ignoreId): string
    {
        $base = $slug !== '' ? $slug : 'post';
        $candidate = $base; $n = 2;
        while (Database::one('SELECT id FROM posts WHERE slug=? AND id<>? LIMIT 1', [$candidate, $ignoreId])) $candidate = substr($base, 0, 170) . '-' . $n++;
        return $candidate;
    }

    public function save(): void
    {
        $this->requirePost(); $u = Auth::requireAdmin(); Auth::enforce('manage_content');
        $id = (int)($_POST['id'] ?? 0);
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 200);
        $type = in_array($_POST['post_type'] ?? 'news', ['news', 'blog'], true) ? $_POST['post_type'] : 'news';
        $status = in_array($_POST['status'] ?? 'draft', self::STATUSES, true) ? $_POST['status'] : 'draft';
        if (mb_strlen($title) < 3) { Session::flash('error', 'Enter a post title of at least 3 characters.'); redirect('admin/posts'); }
        $old = $id ? Database::one('SELECT * FROM posts WHERE id=? LIMIT 1', [$id]) : null;
        if ($id && !$old) { Session::flash('error', 'Post not found.'); redirect('admin/posts'); }

        $cats = self::categories();
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if ($categoryId && !in_array($categoryId, array_map(static fn($c) => (int)$c['id'], $cats), true)) $categoryId = 0;

        // Readable, unique URL. Published links only change when an administrator edits the URL field on purpose.
        $requested = slugify((string)($_POST['slug'] ?? ''));
        $slug = self::uniqueSlug($requested !== '' ? $requested : ($old['slug'] ?? slugify($title)), $id);

        $published = trim((string)($_POST['published_at'] ?? ''));
        $publishedAt = $published !== '' && strtotime($published) ? date('Y-m-d H:i:s', strtotime($published)) : ($old['published_at'] ?? null);
        if ($status === 'published' && !$publishedAt) $publishedAt = date('Y-m-d H:i:s');

        $img = $old['featured_image'] ?? null;
        try {
            if (isset($_FILES['featured_image']) && ($_FILES['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $img = Upload::publicImage($_FILES['featured_image'], 'posts');
            elseif (isset($_POST['remove_image'])) $img = null;
        } catch (\Throwable $e) { Session::flash('error', $e->getMessage()); redirect('admin/posts'); }

        $data = [
            'post_type' => $type, 'title' => $title, 'slug' => $slug,
            'excerpt' => mb_substr(trim((string)($_POST['excerpt'] ?? '')), 0, 800),
            'content' => mb_substr(str_replace("\r\n", "\n", trim((string)($_POST['content'] ?? ''))), 0, 50000),
            'featured_image' => $img, 'status' => $status, 'is_featured' => isset($_POST['is_featured']) ? 1 : 0, 'published_at' => $publishedAt,
            'meta_title' => mb_substr(trim((string)($_POST['meta_title'] ?? '')), 0, 240),
            'meta_description' => mb_substr(trim((string)($_POST['meta_description'] ?? '')), 0, 500),
        ];
        if ($cats) $data['category_id'] = $categoryId ?: null;
        $cols = array_keys($data);
        if ($id) {
            Database::exec('UPDATE posts SET ' . implode(',', array_map(static fn($c) => $c . '=?', $cols)) . ',updated_by=? WHERE id=?', array_merge(array_values($data), [(int)$u['id'], $id]));
        } else {
            $id = Database::insert('INSERT INTO posts(' . implode(',', $cols) . ',created_by,updated_by) VALUES(' . implode(',', array_fill(0, count($cols), '?')) . ',?,?)', array_merge(array_values($data), [(int)$u['id'], (int)$u['id']]));
        }
        Audit::write((int)$u['id'], 'POST_SAVED', 'posts', $id, ucfirst($type) . ' "' . $title . '" saved as ' . $status);
        $live = $status === 'published' && strtotime((string)$publishedAt) <= time();
        $msg = $live ? 'Published. The article is live at ' . absolute_url('news/' . $slug)
            : ($status === 'published' ? 'Scheduled for ' . date('d M Y, H:i', strtotime((string)$publishedAt)) . '. It goes live automatically.' : 'Saved as ' . $status . '. It is not visible on the website.');
        if ($old && $old['slug'] !== $slug && $old['status'] === 'published') $msg .= ' The web address changed, so links shared earlier will no longer open this article.';
        Session::flash('success', $msg);
        redirect('admin/posts');
    }

    /** One-click publish / unpublish. */
    public function status(): void
    {
        $this->requirePost(); $u = Auth::requireAdmin(); Auth::enforce('manage_content');
        $id = (int)($_POST['id'] ?? 0); $to = $_POST['to'] ?? '';
        $p = Database::one('SELECT id,title,status,published_at,slug FROM posts WHERE id=? LIMIT 1', [$id]);
        if (!$p || !in_array($to, ['published', 'draft'], true)) { Session::flash('error', 'Post not found.'); redirect('admin/posts'); }
        if ($to === 'published') {
            $when = $p['published_at'] && strtotime($p['published_at']) <= time() ? $p['published_at'] : date('Y-m-d H:i:s');
            Database::exec('UPDATE posts SET status="published",published_at=?,updated_by=? WHERE id=?', [$when, (int)$u['id'], $id]);
            Session::flash('success', '"' . $p['title'] . '" is now live at ' . absolute_url('news/' . $p['slug']));
        } else {
            Database::exec('UPDATE posts SET status="draft",updated_by=? WHERE id=?', [(int)$u['id'], $id]);
            Session::flash('success', '"' . $p['title'] . '" was unpublished and is no longer visible on the website.');
        }
        Audit::write((int)$u['id'], $to === 'published' ? 'POST_PUBLISHED' : 'POST_UNPUBLISHED', 'posts', $id, $p['title']);
        redirect('admin/posts');
    }

    public function delete(): void
    {
        $this->requirePost(); $u = Auth::requireAdmin(); Auth::enforce('manage_content');
        $id = (int)($_POST['id'] ?? 0);
        $p = Database::one('SELECT title FROM posts WHERE id=?', [$id]);
        Database::exec('DELETE FROM posts WHERE id=?', [$id]);
        Audit::write((int)$u['id'], 'DELETE', 'posts', $id, 'Post deleted: ' . ($p['title'] ?? '#' . $id));
        Session::flash('success', 'Post deleted.');
        redirect('admin/posts');
    }

    public function categorySave(): void
    {
        $this->requirePost(); $u = Auth::requireAdmin(); Auth::enforce('manage_content');
        $id = (int)($_POST['id'] ?? 0);
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 120);
        if (mb_strlen($name) < 2) { Session::flash('error', 'Enter a category name.'); redirect('admin/posts#categories'); }
        $slug = slugify((string)($_POST['slug'] ?? '')) ?: slugify($name);
        if ($slug === '') $slug = 'category';
        $base = $slug; $n = 2;
        while (Database::one('SELECT id FROM post_categories WHERE slug=? AND id<>? LIMIT 1', [$slug, $id])) $slug = $base . '-' . $n++;
        $desc = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 400) ?: null;
        $active = isset($_POST['is_active']) || !$id ? 1 : 0;
        if ($id) Database::exec('UPDATE post_categories SET name=?,slug=?,description=?,is_active=? WHERE id=?', [$name, $slug, $desc, $active, $id]);
        else { $order = (int)(Database::one('SELECT COALESCE(MAX(sort_order),0) m FROM post_categories')['m'] ?? 0) + 10; $id = Database::insert('INSERT INTO post_categories(name,slug,description,sort_order,is_active) VALUES(?,?,?,?,1)', [$name, $slug, $desc, $order]); }
        Audit::write((int)$u['id'], 'POST_CATEGORY_SAVED', 'post_categories', $id, 'Category "' . $name . '" saved');
        Session::flash('success', 'Category "' . $name . '" saved. Its page is ' . absolute_url('news/category/' . $slug));
        redirect('admin/posts#categories');
    }

    public function categoryDelete(): void
    {
        $this->requirePost(); $u = Auth::requireAdmin(); Auth::enforce('manage_content');
        $id = (int)($_POST['id'] ?? 0);
        $c = Database::one('SELECT name FROM post_categories WHERE id=?', [$id]);
        if (!$c) { Session::flash('error', 'Category not found.'); redirect('admin/posts#categories'); }
        Database::exec('UPDATE posts SET category_id=NULL WHERE category_id=?', [$id]);
        Database::exec('DELETE FROM post_categories WHERE id=?', [$id]);
        Audit::write((int)$u['id'], 'DELETE', 'post_categories', $id, 'Category deleted: ' . $c['name']);
        Session::flash('success', 'Category "' . $c['name'] . '" deleted. Its posts are kept and now have no category.');
        redirect('admin/posts#categories');
    }
}
