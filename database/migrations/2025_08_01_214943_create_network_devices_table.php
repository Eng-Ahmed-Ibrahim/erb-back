<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('network_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->uuid('department_id')->nullable();
            $table->string('mac_address')->unique();
            $table->string('ip_address')->nullable();
            $table->string('device_id')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('network_devices');
    }
};
