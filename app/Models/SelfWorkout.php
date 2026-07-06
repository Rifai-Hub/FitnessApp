<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['judul', 'deskripsi', 'instruksi', 'url_media', 'kategori', 'dibuat_oleh_superadmin'])]
class SelfWorkout extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function superadmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_superadmin');
    }
}
