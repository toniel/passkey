<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->text('name');
            $table->text('credential_id');
            $table->binary('publicKeyCredentialSource')->nullable();
            $table->binary('publicKeyCredentialId')->nullable();
            $table->binary('credentialPublicKey')->nullable();
            $table->json('metadata');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passkeys');
    }
};
