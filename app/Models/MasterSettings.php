<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterSettings extends Model
{
    use HasFactory;
    protected $fillable=['master_title','master_value'];
    public $timestamps = false;
    
    protected static function booted()
    {
        $clearCache = function () {
            \Illuminate\Support\Facades\Cache::forget('master_settings');
        };
        static::saved($clearCache);
        static::deleted($clearCache);
    }

    /* master settings value update settings */
    public function siteData(){
        return \Illuminate\Support\Facades\Cache::rememberForever('master_settings', function () {
            $siteInfo = array();
            foreach($this->get() as $key=>$value){
                $siteInfo[$value['master_title']] = $value['master_value'];
            }
            return $siteInfo;
        });
    }
}
