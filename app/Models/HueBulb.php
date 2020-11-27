<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\Bluetooth;

class HueBulb extends Model
{
    use HasFactory;

    protected $fillable = [
        'mac'
    ];


    const POWER_SERVICE_UUID = "932c32bd-0000-47a2-835a-a8d455b859dd";
    const POWER_CHARACTERISTIC_UUID = "932c32bd-0002-47a2-835a-a8d455b859dd";

    public function isLit(): bool
    {
        $bt = resolve(Bluetooth::class);
        return $bt->readCharacteristic($this->mac, self::POWER_SERVICE_UUID, self::POWER_CHARACTERISTIC_UUID)[0] == 1;
    }

    public function SetState(bool $lit): void
    {
        $bt = resolve(Bluetooth::class);
        $bt->writeCharacteristic($this->mac, self::POWER_SERVICE_UUID, self::POWER_CHARACTERISTIC_UUID, array($lit ? 1 : 0));

    }
}
