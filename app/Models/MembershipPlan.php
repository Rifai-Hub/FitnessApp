<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'durasi_bulan', 'harga'])]
class MembershipPlan extends Model
{
    protected function casts(): array
    {
        return [
            'durasi_bulan' => 'integer',
            'harga' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
