<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->unique()->primary();
            $table->string('slug');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->enum('gender', ['Male', 'Female']);
            $table->date('birth_date')->nullable();
            $table->tinyText('bio')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('dark_mode')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamps();
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
    }
}
