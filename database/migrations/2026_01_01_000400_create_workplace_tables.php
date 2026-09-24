<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title', 160);
            $t->text('body');
            $t->string('audience', 60)->default('all');
            $t->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->index(['audience','published_at']);
        });

        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->string('title', 160);
            $t->string('stored_path');                 // private disk, generated name
            $t->string('original_name', 190);
            $t->string('mime', 100);
            $t->unsignedBigInteger('size_bytes');
            $t->char('checksum_sha256', 64)->nullable();
            $t->unsignedTinyInteger('sector_id')->nullable();
            $t->enum('classification', ['public','internal','restricted'])->default('internal');
            $t->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->foreign('sector_id')->references('id')->on('sectors')->nullOnDelete();
            $t->index(['classification','sector_id']);
        });

        Schema::create('document_downloads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['document_id','created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('actor', 190)->nullable();
            $t->string('action', 80);
            $t->string('target', 190)->nullable();
            $t->enum('result', ['allowed','denied']);
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->json('context')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['action','result','created_at']);
            $t->index(['actor','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('document_downloads');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('announcements');
    }
};
