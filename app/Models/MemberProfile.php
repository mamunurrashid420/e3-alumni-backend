<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MemberProfile extends Model
{
    /**
     * Directory under the public disk for member profile files (photo, signature).
     */
    public const STORAGE_DIR = 'member-profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name_bangla',
        'father_name',
        'mother_name',
        'gender',
        'jsc_year',
        'ssc_year',
        'highest_educational_degree',
        'present_address',
        'permanent_address',
        'profession',
        'designation',
        'institute_name',
        't_shirt_size',
        'blood_group',
        'photo',
        'signature',
    ];

    /**
     * Get the user that owns the member profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Copy photo and signature from application storage into member-profiles/{userId}/.
     * Returns paths to store on the MemberProfile (or null if source missing).
     *
     * @return array{photo: string|null, signature: string|null}
     */
    public static function copyFilesFromApplicationPath(?string $photoPath, ?string $signaturePath, int $userId): array
    {
        $disk = Storage::disk('public');
        $baseDir = self::STORAGE_DIR.'/'.$userId;

        $photo = null;
        if ($photoPath && $disk->exists($photoPath)) {
            $ext = pathinfo($photoPath, PATHINFO_EXTENSION) ?: 'jpg';
            $dest = $baseDir.'/photo.'.$ext;
            $disk->copy($photoPath, $dest);
            $photo = $dest;
        }

        $signature = null;
        if ($signaturePath && $disk->exists($signaturePath)) {
            $ext = pathinfo($signaturePath, PATHINFO_EXTENSION) ?: 'png';
            $dest = $baseDir.'/signature.'.$ext;
            $disk->copy($signaturePath, $dest);
            $signature = $dest;
        }

        return ['photo' => $photo, 'signature' => $signature];
    }
}
