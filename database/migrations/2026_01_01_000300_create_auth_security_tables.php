<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_codes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('code_hash');                 // hashed, never the code
            $t->enum('channel', ['email','sms']);
            $t->string('destination', 190);
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('consumed_at')->nullable();
            $t->timestamp('expires_at');
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['user_id','consumed_at','expires_at']);
        });

        Schema::create('login_attempts', function (Blueprint $t) {
            $t->id();
            $t->string('email', 190);
            $t->string('ip', 45)->nullable();
            $t->boolean('successful')->default(false);
            $t->enum('stage', ['password','mfa'])->default('password');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['email','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('mfa_codes');
    }
};
