<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->unsignedTinyInteger('role_id')->nullable()->after('password');
            $t->unsignedTinyInteger('sector_id')->nullable()->after('role_id');
            $t->unsignedSmallInteger('site_id')->nullable()->after('sector_id');
            $t->string('position', 90)->nullable()->after('site_id');
            $t->text('phone_encrypted')->nullable()->after('position');   // AES-256 at rest
            $t->char('phone_last2', 2)->nullable()->after('phone_encrypted');
            $t->enum('mfa_channel', ['email','sms'])->default('email')->after('phone_last2');
            $t->boolean('mfa_enabled')->default(true)->after('mfa_channel');
            $t->timestamp('password_changed_at')->nullable()->after('mfa_enabled');
            $t->boolean('must_change_password')->default(false)->after('password_changed_at');
            $t->unsignedTinyInteger('failed_logins')->default(0)->after('must_change_password');
            $t->timestamp('locked_until')->nullable()->after('failed_logins');
            $t->boolean('is_active')->default(true)->after('locked_until');

            $t->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
            $t->foreign('sector_id')->references('id')->on('sectors')->nullOnDelete();
            $t->foreign('site_id')->references('id')->on('sites')->nullOnDelete();
            $t->index(['is_active','sector_id']);
        });

        Schema::create('password_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('password_hash');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['user_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_histories');
        Schema::table('users', function (Blueprint $t) {
            $t->dropForeign(['role_id']); $t->dropForeign(['sector_id']); $t->dropForeign(['site_id']);
            $t->dropColumn(['role_id','sector_id','site_id','position','phone_encrypted','phone_last2',
                'mfa_channel','mfa_enabled','password_changed_at','must_change_password',
                'failed_logins','locked_until','is_active']);
        });
    }
};
