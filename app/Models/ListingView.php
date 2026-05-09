<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingView extends Model
{
    protected $fillable = ['listing_id', 'day', 'count'];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }

    /** Day is stored as a plain Y-m-d string (no datetime cast, to avoid
     * format mismatch with the unique index across SELECT and INSERT). */
    protected $dates = [];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
