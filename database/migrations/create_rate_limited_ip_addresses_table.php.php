<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rate_limited_ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('redis_id');
            $table->string('ip');
            $table->string('url');
            $table->string('path');
            $table->string('method');
            $table->json('headers');
            $table->json('query');
            $table->json('body');
            $table->integer('attempts')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rate_limited_ip_addresses');
    }
};
