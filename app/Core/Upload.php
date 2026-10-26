<?php
namespace App\Core;
use RuntimeException;
final class Upload
{
    private static function validatedImage(array $file,int $max=5242880): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed.');
        if(($file['size']??0)>$max) throw new RuntimeException('Image exceeds the 5 MB limit.');
        $finfo=new \finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if(!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
        if(@getimagesize($file['tmp_name'])===false) throw new RuntimeException('Invalid image content.');
        return [$mime,$allowed[$mime]];
    }
    public static function publicImage(array $file,string $folder='media'): string
    {
        [, $ext]=self::validatedImage($file);
        $folder=preg_replace('/[^a-z0-9_-]/i','',$folder)?:'media';
        $name=bin2hex(random_bytes(20)).'.'.$ext;
        $base=dirname(__DIR__,2).'/public/uploads/'.$folder; if(!is_dir($base)) mkdir($base,0755,true);
        $dest=$base.'/'.$name; if(!move_uploaded_file($file['tmp_name'],$dest)) throw new RuntimeException('Could not store uploaded image.');
        @chmod($dest,0644); return $folder.'/'.$name;
    }

    public static function publicMemberPhoto(array $file): string
    {
        $path=self::publicImage($file,'members');
        self::optimizeMemberPhoto($path);
        return $path;
    }

    public static function optimizeMemberPhoto(string $path): void
    {
        if(!extension_loaded('gd') || !function_exists('imagewebp')) return;
        $clean=ltrim($path,'/');
        if($clean==='' || str_contains($clean,'..')) return;
        $root=dirname(__DIR__,2).'/public/uploads';
        $source=$root.'/'.$clean;
        if(!is_file($source)) return;
        $info=@getimagesize($source);
        if(!$info || empty($info[0]) || empty($info[1])) return;
        [$srcW,$srcH]=$info;
        $mime=(string)($info['mime']??'');
        $img=match($mime){
            'image/jpeg'=>@imagecreatefromjpeg($source),
            'image/png'=>@imagecreatefrompng($source),
            'image/webp'=>@imagecreatefromwebp($source),
            default=>false,
        };
        if(!$img) return;
        if($mime==='image/jpeg' && function_exists('exif_read_data')){
            $exif=@exif_read_data($source);
            $orientation=(int)($exif['Orientation']??1);
            if($orientation===3){$img=imagerotate($img,180,0);[$srcW,$srcH]=[$srcW,$srcH];}
            elseif($orientation===6){$img=imagerotate($img,-90,0);[$srcW,$srcH]=[$srcH,$srcW];}
            elseif($orientation===8){$img=imagerotate($img,90,0);[$srcW,$srcH]=[$srcH,$srcW];}
        }
        $dir=dirname($clean);$stem=pathinfo($clean,PATHINFO_FILENAME);
        $variantDir=$root.'/'.$dir.'/optimized';
        if(!is_dir($variantDir)) @mkdir($variantDir,0755,true);
        foreach([360,720] as $targetW){
            if($srcW<$targetW) continue;
            $targetH=max(1,(int)round($srcH*($targetW/$srcW)));
            $canvas=imagecreatetruecolor($targetW,$targetH);
            imagealphablending($canvas,false);imagesavealpha($canvas,true);
            $transparent=imagecolorallocatealpha($canvas,0,0,0,127);imagefilledrectangle($canvas,0,0,$targetW,$targetH,$transparent);
            imagecopyresampled($canvas,$img,0,0,0,0,$targetW,$targetH,$srcW,$srcH);
            $dest=$variantDir.'/'.$stem.'-'.$targetW.'.webp';
            if(@imagewebp($canvas,$dest,82)) @chmod($dest,0644);
            imagedestroy($canvas);
        }
        imagedestroy($img);
    }
    public static function removePublicImage(?string $path): void
    {
        $clean=ltrim((string)$path,'/');
        if($clean==='' || str_contains($clean,'..')) return;
        $root=realpath(dirname(__DIR__,2).'/public/uploads');
        $file=dirname(__DIR__,2).'/public/uploads/'.$clean;
        if(!$root || !is_file($file)) return;
        $real=realpath($file);
        if($real && str_starts_with($real,$root.DIRECTORY_SEPARATOR)) @unlink($real);
        $stem=pathinfo($clean,PATHINFO_FILENAME);$dir=dirname($clean);
        foreach([360,720] as $w){
            $variant=dirname(__DIR__,2).'/public/uploads/'.$dir.'/optimized/'.$stem.'-'.$w.'.webp';
            if(is_file($variant)) @unlink($variant);
        }
    }

    /** Accepted payment receipt formats (MIME => stored extension). */
    public const RECEIPT_TYPES=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];

    public static function receiptMaxBytes(): int
    {
        return max(1048576,(int)App::config('security.max_upload_bytes',8388608));
    }

    public static function donationProof(array $file): string
    {
        $max=self::receiptMaxBytes();$mb=(int)round($max/1048576);
        $error=(int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) throw new RuntimeException('Upload your payment receipt to submit this donation.');
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) throw new RuntimeException('The receipt is larger than '.$mb.' MB. Upload a smaller photo or PDF.');
        if ($error === UPLOAD_ERR_PARTIAL) throw new RuntimeException('The receipt upload was interrupted. Please choose the file again.');
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) throw new RuntimeException('The receipt could not be uploaded. Please try again.');
        $size=(int)($file['size']??0);
        if($size<=0) throw new RuntimeException('The receipt file is empty. Choose the original photo or PDF.');
        if($size>$max) throw new RuntimeException('The receipt is larger than '.$mb.' MB. Upload a smaller photo or PDF.');
        $finfo=new \finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
        $allowed=self::RECEIPT_TYPES;
        if(!isset($allowed[$mime])) throw new RuntimeException('Receipts must be a JPG, PNG, WEBP image or a PDF file.');
        if(str_starts_with((string)$mime,'image/') && @getimagesize($file['tmp_name'])===false) throw new RuntimeException('The receipt image could not be read. Upload a clear photo or screenshot.');
        if($mime==='application/pdf'){$head=(string)@file_get_contents($file['tmp_name'],false,null,0,5);if($head!=='%PDF-')throw new RuntimeException('The PDF receipt appears to be damaged. Export it again and retry.');}
        $name=bin2hex(random_bytes(24)).'.'.$allowed[$mime];
        $dir=dirname(__DIR__,2).'/storage/private/donation-proofs'; if(!is_dir($dir)) mkdir($dir,0750,true);
        $dest=$dir.'/'.$name; if(!move_uploaded_file($file['tmp_name'],$dest)) throw new RuntimeException('Could not store uploaded proof.');
        @chmod($dest,0640); return $name;
    }
}
