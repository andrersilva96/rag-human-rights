<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('section');
            $table->unsignedInteger('chunk_number');
            $table->text('content');
            $table->string('hash', 64);
            $table->vector('embedding', 1536); // text-embedding-3-small
            $table->timestamps();

            $table->unique(['source', 'chunk_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chunks');
    }
};
