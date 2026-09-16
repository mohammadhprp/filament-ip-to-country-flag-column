<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = ['ip_address'];
}
