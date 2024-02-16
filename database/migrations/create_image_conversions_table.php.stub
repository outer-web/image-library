<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('image_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('conversion_name');
            $table->string('conversion_md5');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('size');
            $table->unsignedInteger('x')
                ->nullable();
            $table->unsignedInteger('y')
                ->nullable();
            $table->unsignedInteger('rotate')
                ->default(0);
            $table->tinyInteger('scale_x')
                ->default(1);
            $table->tinyInteger('scale_y')
                ->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_conversions');
    }
};
