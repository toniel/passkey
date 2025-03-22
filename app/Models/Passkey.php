<?php

namespace App\Models;

use App\Models\User;
use App\Support\JsonSerializer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialSource;

class Passkey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','credential_id','metadata','publicKeyCredentialSource','publicKeyCredentialId','credentialPublicKey'
    ];


    // protected function casts(): array{
    //     return [
    //         'data'=>'json'
    //     ];
    // }

    public function metadata(): Attribute
    {
        return new Attribute(
            get: fn (string $value) => JsonSerializer::deserialize($value, PublicKeyCredential::class),
            // set: fn (PublicKeyCredentialSource $value) => [
            //     'credential_id' => $value->publicKeyCredentialId,
            //     'data' => JsonSerializer::serialize($value),
            // ],
        );
    }

    /**
     * Get the user that owns the Passkey
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
