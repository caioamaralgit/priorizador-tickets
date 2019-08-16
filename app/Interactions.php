<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Interactions extends Model
{
    const UPDATED_AT = null;

    protected $table = "interactions";
    protected $fillable = ["tickets_id", "subject", "message", "sender"];
    
    protected function ticket()
    {
        return $this->belongsTo("App\Tickets", "tickets_id");
    }
}
