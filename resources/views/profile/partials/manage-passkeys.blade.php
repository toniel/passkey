<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Manage Passkeys') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Passkeys allow for a more secure, seamless authentication experience on supported devices.') }}
        </p>
    </header>

    <form x-data="registerPasskey"  x-on:submit.prevent="register($el)"  name="createPasskey" method="POST" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label for="create_passkey_passkey_name" :value="__('Passkey Name')"/>
            <x-text-input id="create_passkey_passkey_name" x-model="form.name" name="name" class="block w-full mt-1"/>

            <template x-if="errors && errors.name">
                <span class="space-y-1 text-sm text-red-600" x-text="errors.name"></span>
            </template>

            <x-input-error :messages="$errors->createPasskey->get('name')" class="mt-2"/>
                {{-- <span x-text="form.name"></span> --}}
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Create Passkey') }}</x-primary-button>
            <p x-show="notification" class="mt-2 text-sm font-medium text-green-600">
                {{ __('Passkey Tersimpan') }}
            </p>
        </div>

    </form>

    <div class="mt-6">
        <h3 class="font-medium text-gray-900">{{ __('Your Passkeys') }} </h3>
        <ul class="mt-2">


            @foreach ($user->passkeys as $passkey)
            <li class="flex items-center justify-between px-2 py-2">
                <div class="flex flex-col">
                    <span class="font-semibold">{{ $passkey->name }}</span>
                    <span class="text-sm font-thin text-gray-600">Added {{ $passkey->created_at->diffForHumans() }}</span>
                </div>

                <form method="post" action="/">
                    @csrf
                    @method('DELETE')

                    <input type="hidden" name="id" value="">
                    <x-danger-button class="">Remove</x-danger-button>
                </form>
            </li>
            @endforeach



        </ul>
    </div>
</section>
