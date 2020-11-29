<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LightGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function bulbs()
    {
        return $this->belongsToMany('App\Models\HueBulb');
    }

    # TODO: investigate best enum option
    public function LitStatus(): ?String
    {
        if($this->bulbs->count() == 0){
            return null;
        }
        $seenOn = $seenOff = false;
        foreach($this->bulbs as $bulb){
            $on = $bulb->isLit();
            $seenOn = $seenOn || $on;
            $seenOff = $seenOff || !$on;
            if(($on && $seenOff) || (!$on && $seenOn)){
                return "inconsistent";
            }
        }
        return $seenOn ? "lit" : "extinguished";
    }

    public function SetState(bool $lit): void
    {
        foreach($this->bulbs as $bulb){
            $bulb->SetState($lit);
        }
    }


}
