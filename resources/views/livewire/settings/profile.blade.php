<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            
            <div>
                <flux:label>{{ __('Profile photo') }}</flux:label>

                <div class="mt-1 flex items-center gap-4">
                    <!-- Current image -->
                    <div class="w-20 h-20 rounded overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                        @if($photo)
                            <!-- temporary preview for newly selected file -->
                            <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                        @elseif($currentImageUrl)
                            <!-- stored image -->
                            <img src="{{ $currentImageUrl }}" alt="Profile" class="w-full h-full object-cover">
                        @else
                            <!-- placeholder initials -->
                            <div class="text-xl font-semibold text-zinc-600 dark:text-zinc-300">{{ auth()->user()->initials() }}</div>
                        @endif
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                type="file"
                                wire:model="photo"
                                accept="image/*"
                                class="hidden"
                            />
                            <span class="px-3 py-2 bg-[#2b80ff] text-white rounded text-sm">Choose photo</span>
                        </label>

                        @error('photo')
                            <div class="mt-2 text-xs text-red-600">{{ $message }}</div>
                        @enderror

                        <!-- upload progress (simple) -->
                        <div wire:loading wire:target="photo" class="mt-2 text-xs text-zinc-500">
                            Uploading...
                        </div>
                    </div>
                </div>
            </div>

            <div class="">
                <div>
                    <flux:input wire:model.defer="first_name" :label="__('First Name')" type="text" required autofocus autocomplete="first_name" />
                </div>
            
                <div class="mt-4">
                    <flux:input wire:model.defer="last_name" :label="__('Last Name')" type="text" required autofocus autocomplete="last_name" />
                </div>

                <div class="mt-4">
                    <flux:input wire:model.defer="email" :label="__('Email')" type="email" required autocomplete="email" />
                    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                        <div>
                            <flux:text class="mt-4">
                                {{ __('Your email address is unverified.') }}

                                <a class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Click here to re-send the verification email.') }}
                                </a>
                            </flux:text>

                            @if (session('status') === 'verification-link-sent')
                                <span class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
