<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'jumlah_sesi', 'masa_berlaku_hari', 'harga'])]
class PersonalTrainerPackage extends Model
{
    protected function casts(): array
    {
        return [
            'jumlah_sesi' => 'integer',
            'masa_berlaku_hari' => 'integer',
            'harga' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<MembershipPlan, $this>
     */
    public function membershipPlans(): HasMany
    {
        return $this->hasMany(MembershipPlan::class);
    }
}
