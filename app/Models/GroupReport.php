<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $total aggregate/computed alias
 */
class GroupReport extends Model
{
    use HasFactory;
}
