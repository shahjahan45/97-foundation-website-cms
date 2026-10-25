<?php
namespace App\Core;

/**
 * Header-ready brand images.
 *
 * Uploaded logos often contain large empty margins (e.g. a 1536 × 1024 canvas
 * with the artwork in the middle), which makes them look tiny in the header.
 * This trims the empty space, keeps transparency and stores a web-sized PNG.
 */
final class BrandImage
{
    /** Returns the new relative upload path, or null when the image could not be processed. */
    public static function optimize(string $relativePath, int $maxHeight = 480): ?string
    {
        if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) return null;
        $clean = ltrim($relativePath, '/');
        if ($clean === '' || str_contains($clean, '..')) return null;
        $root = dirname(__DIR__, 2) . '/public/uploads';
        $source = $root . '/' . $clean;
        $info = @getimagesize($source);
        if (!$info || empty($info[0]) || empty($info[1])) return null;
        $img = match ((string)($info['mime'] ?? '')) {
            'image/png' => @imagecreatefrompng($source),
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };
        if (!$img) return null;
        if (!imageistruecolor($img)) imagepalettetotruecolor($img);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        [$w, $h] = [imagesx($img), imagesy($img)];
        $box = self::contentBox($img, $w, $h);
        if (!$box) { imagedestroy($img); return null; }
        [$x0, $y0, $x1, $y1] = $box;
        $cw = $x1 - $x0 + 1;
        $ch = $y1 - $y0 + 1;
        $pad = (int)round(max($cw, $ch) * 0.03);
        $x0 = max(0, $x0 - $pad); $y0 = max(0, $y0 - $pad);
        $cw = min($w - $x0, $cw + $pad * 2); $ch = min($h - $y0, $ch + $pad * 2);

        $scale = min(1, $maxHeight / $ch);
        $tw = max(1, (int)round($cw * $scale));
        $th = max(1, (int)round($ch * $scale));
        $out = imagecreatetruecolor($tw, $th);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefilledrectangle($out, 0, 0, $tw, $th, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $img, 0, 0, $x0, $y0, $tw, $th, $cw, $ch);

        $dir = dirname($clean);
        $stem = preg_replace('/-header$/', '', pathinfo($clean, PATHINFO_FILENAME));
        $target = ($dir === '.' ? '' : $dir . '/') . $stem . '-header.png';
        $ok = @imagepng($out, $root . '/' . $target, 8);
        imagedestroy($out);
        imagedestroy($img);
        if (!$ok) return null;
        @chmod($root . '/' . $target, 0644);
        return $target;
    }

    /** Bounding box of visible artwork: transparent pixels, or the corner background colour for opaque images. */
    private static function contentBox(\GdImage $img, int $w, int $h): ?array
    {
        $corner = imagecolorat($img, 0, 0);
        $cornerAlpha = ($corner >> 24) & 0x7F;
        $useAlpha = $cornerAlpha > 100;
        $bg = [($corner >> 16) & 0xFF, ($corner >> 8) & 0xFF, $corner & 0xFF];
        $step = max(1, (int)floor(max($w, $h) / 900));
        $x0 = $w; $y0 = $h; $x1 = -1; $y1 = -1;
        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                $c = imagecolorat($img, $x, $y);
                $alpha = ($c >> 24) & 0x7F; // 0 = opaque, 127 = transparent
                if ($useAlpha) {
                    $visible = $alpha < 110;
                } else {
                    $d = abs((($c >> 16) & 0xFF) - $bg[0]) + abs((($c >> 8) & 0xFF) - $bg[1]) + abs(($c & 0xFF) - $bg[2]);
                    $visible = $alpha < 110 && $d > 36;
                }
                if (!$visible) continue;
                if ($x < $x0) $x0 = $x; if ($x > $x1) $x1 = $x;
                if ($y < $y0) $y0 = $y; if ($y > $y1) $y1 = $y;
            }
        }
        if ($x1 < 0) return null;
        return [max(0, $x0 - $step), max(0, $y0 - $step), min($w - 1, $x1 + $step), min($h - 1, $y1 + $step)];
    }
}
