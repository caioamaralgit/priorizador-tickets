<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tickets extends Model
{
    protected $table = "tickets";
    protected $fillable = ["id", "categories_id", "customers_id", "customer_name", 
        "customer_email", "score", "priority", "created_at", "updated_at"];
    
    public function interactions()
    {
        return $this->hasMany('App\Interactions', 'tickets_id');
    }
}
