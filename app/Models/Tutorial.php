<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['judul', 'deskripsi', 'url_media', 'kategori', 'dibuat_oleh_superadmin'])]
class Tutorial extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function superadmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_superadmin');
    }
}
