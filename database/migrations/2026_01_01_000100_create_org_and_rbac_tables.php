<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $t) {
            $t->tinyIncrements('id');
            $t->string('name', 60)->unique();
            $t->string('description', 190)->nullable();
            $t->timestamps();
        });

        Schema::create('sites', function (Blueprint $t) {
            $t->smallIncrements('id');
            $t->unsignedTinyInteger('sector_id');
            $t->string('name', 120);
            $t->string('commune', 80)->nullable();
            $t->timestamps();
            $t->foreign('sector_id')->references('id')->on('sectors')->cascadeOnDelete();
        });

        Schema::create('roles', function (Blueprint $t) {
            $t->tinyIncrements('id');
            $t->string('name', 50)->unique();
            $t->string('description', 190)->nullable();
            $t->boolean('is_system')->default(false);
            $t->timestamps();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->smallIncrements('id');
            $t->string('key', 60)->unique();
            $t->string('description', 190);
            $t->string('group', 40)->default('general');
            $t->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $t) {
            $t->unsignedTinyInteger('role_id');
            $t->unsignedSmallInteger('permission_id');
            $t->primary(['role_id','permission_id']);
            $t->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $t->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('sectors');
    }
};
