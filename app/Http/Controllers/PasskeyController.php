<?php

namespace App\Http\Controllers;

use App\Models\Passkey;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Support\JsonSerializer;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredential;
use Illuminate\Support\Facades\Auth;
use Webauthn\TrustPath\EmptyTrustPath;
use Illuminate\Support\Facades\Session;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorSelectionCriteria;
use Illuminate\Validation\ValidationException;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

class PasskeyController extends Controller
{


    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'answer' => ['required', 'json']
        ]);


        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());

        $factory = new WebauthnSerializerFactory($attestationStatementSupportManager);
        $serializer = $factory->create();





        $publicKeyCredential = JsonSerializer::deserialize($data['answer'], PublicKeyCredential::class);



        if (!$publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            //e.g. process here with a redirection to the public key creation page.
            return to_route('profile.edit')->withFragment('managePasskeys');
        }


        // try {
        //     $publicKeyCredentialSource = AuthenticatorAssertionResponseValidator::create(
        //         (new CeremonyStepManagerFactory())->requestCeremony()
        //     )->check()
        // } catch (\Throwable $th) {
        //     //throw $th;
        // }


        $csmFactory = new CeremonyStepManagerFactory();
        $creationCSM = $csmFactory->creationCeremony();
        $requestCSM = $csmFactory->requestCeremony();
        $authenticatorAttestationResponseValidator = AuthenticatorAssertionResponseValidator::create($requestCSM);

        $passkey = Passkey::firstWhere('credential_id', base64_encode($publicKeyCredential->rawId));

        // dd($passkey);
        $data = new PublicKeyCredentialSource(
            $passkey->publicKeyCredentialId,
            $passkey->metadata["type"],
            $passkey->metadata["transports"],
            $passkey->metadata["attestationType"],
            new EmptyTrustPath(), // Store the class name
            Uuid::fromString($passkey->metadata["aaguid"]), // Convert UUID to string
            $passkey->credentialPublicKey,
            $passkey->metadata["userHandle"],
            $passkey->metadata["counter"],
            null,
            $passkey->metadata["backupEligible"],
            $passkey->metadata["backupStatus"],
            $passkey->metadata["uvInitialized"]
        );



        if (!$passkey) {
            throw ValidationException::withMessages(['answer' => 'Passkey is invalid']);
        }

        try {
            $publicKeyCredentialSource = $authenticatorAttestationResponseValidator
                ->check(
                    publicKeyCredentialSource: $data,
                    authenticatorAssertionResponse: $publicKeyCredential->response,
                    publicKeyCredentialRequestOptions: Session::get('passkey-authentication-options'),
                    host: $request->getHost(),
                    userHandle: null

                );
        } catch (\Throwable $th) {
            throw ValidationException::withMessages(['answer' => 'Passkey is not valid']);
        }

        Auth::loginUsingId($passkey->user_id);
        $request->session()->regenerate();
        return redirect()->route('dashboard');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // dd($request->passkey);

        $data = $request->validateWithBag('createPasskey', [
            'name' => ['required', 'string'],
            'passkey' => ['required', 'json']
        ]);



        $publicKeyCredential = JsonSerializer::deserialize($data['passkey'], PublicKeyCredential::class);

    //  dd(   $publicKeyCredential);

        if (!$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            //e.g. process here with a redirection to the public key creation page.
            Auth::logout();
            return to_route('login');
        }

        $csmFactory = new CeremonyStepManagerFactory();
        $creationCSM = $csmFactory->creationCeremony();
        $requestCSM = $csmFactory->requestCeremony();
        $authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create($creationCSM);

        try {
            $publicKeyCredentialSource = $authenticatorAttestationResponseValidator
                ->check(
                    authenticatorAttestationResponse: $publicKeyCredential->response,
                    publicKeyCredentialCreationOptions: Session::get('passkey-registration-options'),
                    host: $request->getHost(),

                );
        } catch (\Throwable $th) {
            throw ValidationException::withMessages([
                'name' => 'The given passkey is invalid',
            ])->errorBag('createPasskey');
        }


        $storedData = [
            'publicKeyCredentialId' => base64_encode($publicKeyCredentialSource->publicKeyCredentialId),
            'type' => $publicKeyCredentialSource->type,
            'transports' => $publicKeyCredentialSource->transports,
            'attestationType' => $publicKeyCredentialSource->attestationType,
            'trustPath' => get_class($publicKeyCredentialSource->trustPath), // Store the class name
            'aaguid' => $publicKeyCredentialSource->aaguid->toRfc4122(), // Convert UUID to string
            'credentialPublicKey' => base64_encode($publicKeyCredentialSource->credentialPublicKey),
            'userHandle' => $publicKeyCredentialSource->userHandle,
            'counter' => $publicKeyCredentialSource->counter,
            'backupEligible' => $publicKeyCredentialSource->backupEligible,
            'backupStatus' => $publicKeyCredentialSource->backupStatus,
            'uvInitialized' => $publicKeyCredentialSource->uvInitialized,
        ];



        $request->user()->passkeys()->create([
            'name' => $data['name'],
            'publicKeyCredentialSource'=>json_encode($publicKeyCredentialSource),
            'publicKeyCredentialId'=>$publicKeyCredentialSource->publicKeyCredentialId,
            'credentialPublicKey'=>$publicKeyCredentialSource->credentialPublicKey,
            'credential_id' => base64_encode($publicKeyCredentialSource->publicKeyCredentialId),
            'metadata' => json_encode($storedData), // Safe for UTF-8 JSON storage
        ]);







        // $authenticatorAssertionResponseValidator = AuthenticatorAttestationResponseValidator::create($requestCSM);

        // dd($session);

        // $publicKeyCredential = JsonSerializer::deserialize($session, 'json');

        // dd($publicKeyCredential);

        // return to_route('profile.edit');
        return response()->json([], 201);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Passkey $passkey)
    {
        //
    }
}
