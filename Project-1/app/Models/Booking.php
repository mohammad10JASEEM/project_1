<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_trip_id',
        'destination_trip_id',
        'trip_name',
        'price',
        'number_of_people',
        'start_date',
        'end_date',
        'stars',
        'trip_note',
        'type'
    ];

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function country():BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
