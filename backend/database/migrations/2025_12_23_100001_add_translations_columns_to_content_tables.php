<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('content_translations')->nullable()->after('content');
            $table->json('meta_description_translations')->nullable()->after('meta_description');
        });

        Schema::table('blocks', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('content_translations')->nullable()->after('content');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->json('name_translations')->nullable()->after('description');
            $table->json('description_translations')->nullable()->after('name_translations');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('short_description_translations')->nullable()->after('short_description');
            $table->json('description_translations')->nullable()->after('description');
            $table->json('meta_title_translations')->nullable()->after('meta_title');
            $table->json('meta_description_translations')->nullable()->after('meta_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'title_translations',
                'content_translations',
                'meta_description_translations',
            ]);
        });

        Schema::table('blocks', function (Blueprint $table) {
            $table->dropColumn([
                'title_translations',
                'content_translations',
            ]);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'name_translations',
                'description_translations',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'name_translations',
                'short_description_translations',
                'description_translations',
                'meta_title_translations',
                'meta_description_translations',
            ]);
        });
    }
};
