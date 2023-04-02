<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStartTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('cpf')->unique();
            $table->string('role');
            $table->tinyInteger('status');
            $table->string('password');
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('owner');
        });

        Schema::create('unit_people', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('unit')->constrained('units');
            $table->date('birthdate');
        });

        Schema::create('unit_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('color');
            $table->string('plate');
            $table->unsignedBigInteger('unit');
        });

        Schema::create('unit_pets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('race');
            $table->string('photo');
            $table->unsignedBigInteger('unit');
            $table->tinyText('description');
        });

        Schema::create('walls', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->tinyText('body');
            $table->timestamps();
        });

        Schema::create('wall_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wall')->constrained('walls');
            $table->foreignId('user')->constrained('users');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('file_url');
        });

        Schema::create('billets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit')->constrained('units');
            $table->date('due_date');
            $table->string('file');
            $table->timestamps();
        });

        Schema::create('warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit')->constrained('units');
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('status')->default('IN_REVIEW'); //IN_REVIEW, RESOLVED
            $table->text('photos')->nullable();
            $table->timestamps();
        });

        Schema::create('lost_and_founds', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('LOST'); // LOST, RECOVERED
            $table->string('title');
            $table->string('photo');
            $table->string('where');
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('allowed')->default(1); // 0,1
            $table->string('title');
            $table->string('cover');
            $table->string('days'); // 0,1,2,3,4,5,6
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
        });

        Schema::create('area_disabled_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area')->constrained('areas');
            $table->date('day');
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit')->constrained('units');
            $table->foreignId('area')->constrained('areas');
            $table->datetime('day');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('units');
        Schema::dropIfExists('unit_peoples');
        Schema::dropIfExists('unit_vehicles');
        Schema::dropIfExists('unit_pets');
        Schema::dropIfExists('walls');
        Schema::dropIfExists('wall_likes');
        Schema::dropIfExists('docs');
        Schema::dropIfExists('billets');
        Schema::dropIfExists('warnings');
        Schema::dropIfExists('lost_and_found');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('area_disabled_days');
        Schema::dropIfExists('reservations');
    }
}
