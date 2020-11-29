<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLightGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('light_groups', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
        });

        Schema::create('hue_bulb_light_group', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\HueBulb::class);
            $table->foreignIdFor(\App\Models\LightGroup::class);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('light_groups');
        Schema::dropIfExists('hue_bulb_light_group');
    }
}
