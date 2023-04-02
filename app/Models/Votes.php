<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Votes extends Model
{
    public function assembly(): belongsTo
    {
        return $this->belongsTo(Assembly::class);
    }
}
