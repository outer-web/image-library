<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->uuid()
                ->unique();
            $table->string('disk');
            $table->string('mime_type');
            $table->string('file_extension');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('size');
            $table->json('title')
                ->nullable();
            $table->json('alt')
                ->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
