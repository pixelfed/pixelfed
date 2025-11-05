<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $casts = [
    	'admin_seen' => 'datetime'
    ];

    protected $guarded = [];

    public function url()
    {
        return url('/i/admin/reports/show/'.$this->id);
    }

    public function reported()
    {
        $class = $this->object_type;

        switch ($class) {
            case 'App\Status':
             $column = 'id';
              break;

            default:
             $class = 'App\Status';
             $column = 'id';
              break;
        }

        return (new $class())->where($column, $this->object_id)->first();
    }
}
