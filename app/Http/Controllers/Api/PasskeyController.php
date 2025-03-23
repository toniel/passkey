<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Webauthn\PublicKeyCredential;
use App\Http\Controllers\Controller;
use App\Support\JsonSerializer;
use Illuminate\Support\Facades\Session;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\PublicKeyCredentialRequestOptions;

class PasskeyController extends Controller
{
    public function registerOptions(Request $request)
    {

        $attestationStatementSupportManager=AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());

        $factory = new WebauthnSerializerFactory($attestationStatementSupportManager);
        $serializer = $factory->create();

        $challenge = Str::random();
        $options = new PublicKeyCredentialCreationOptions(
            rp: new PublicKeyCredentialRpEntity(
                name: config('app.name'),
                id: parse_url(config('app.url'), PHP_URL_HOST),
            ),
            user: new PublicKeyCredentialUserEntity(
                name: $request->user()->email,
                id: $request->user()->id,
                displayName: $request->user()->name
            ),
            challenge: $challenge,
            authenticatorSelection:new AuthenticatorSelectionCriteria(
                authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
                // userVerification:AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            )
        );

        // dd($options);

        $jsonObject = $serializer->serialize($options,'json');

        Session::flash('passkey-registration-options', $options);
        // session(['webauthn_challenge' => base64_encode($challenge)]);
        // return $jsonObject;
        return JsonSerializer::serialize($options);

        return response()->json($jsonObject);
    }

    public function authenticateOptions(Request $request){

        $attestationStatementSupportManager=AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());

        $factory = new WebauthnSerializerFactory($attestationStatementSupportManager);
        $serializer = $factory->create();
        $options = new PublicKeyCredentialRequestOptions(
            challenge:Str::random(),
            rpId:parse_url(config('app.url'), PHP_URL_HOST),
            // allowCredentials:
        );

        $jsonObject = $serializer->serialize($options,'json');

        Session::flash('passkey-authentication-options', $options);
        return $jsonObject;


    }
}
