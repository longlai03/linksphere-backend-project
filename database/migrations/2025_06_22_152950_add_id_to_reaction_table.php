<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Xóa các ràng buộc khóa ngoại tạm thời (nếu cần)
        Schema::table('reaction', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['post_id']);
        });

        // Xóa khóa chính cũ
        Schema::table('reaction', function (Blueprint $table) {
            $table->dropPrimary(['user_id', 'post_id']);
        });

        // Thêm cột id tự động tăng
        Schema::table('reaction', function (Blueprint $table) {
            $table->bigIncrements('id')->first();
        });

        // Thêm lại unique cho user_id, post_id
        Schema::table('reaction', function (Blueprint $table) {
            $table->unique(['user_id', 'post_id'], 'reaction_user_post_unique');
        });

        // Thêm lại khóa ngoại
        Schema::table('reaction', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('post')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa các ràng buộc khóa ngoại tạm thời
        Schema::table('reaction', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['post_id']);
        });

        // Xóa unique
        Schema::table('reaction', function (Blueprint $table) {
            $table->dropUnique('reaction_user_post_unique');
        });

        // Xóa cột id
        Schema::table('reaction', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        // Thêm lại composite primary key
        Schema::table('reaction', function (Blueprint $table) {
            $table->primary(['user_id', 'post_id']);
        });

        // Thêm lại khóa ngoại
        Schema::table('reaction', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('post')->onDelete('cascade');
        });
    }
};
