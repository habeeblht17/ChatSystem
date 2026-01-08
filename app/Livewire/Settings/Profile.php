<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Profile extends Component
{
    use WithFileUploads;
    
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public ?TemporaryUploadedFile $photo = null;
    public ?string $currentImageUrl = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
         $user = Auth::user();

        $nameParts = explode(' ', $user->name, 2);
        $this->first_name = $nameParts[0] ?? '';
        $this->last_name = $nameParts[1] ?? '';
        
        $this->email = $user->email;
        $this->currentImageUrl = $user->image ? Storage::url($user->image) : null;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        // Handle photo upload
        $newPath = null;
        if ($this->photo instanceof TemporaryUploadedFile) {
            try {
                $newPath = $this->photo->store('profile_images', 'public');
            } catch (\Throwable $e) {            
                $this->addError('photo', 'Failed to upload image. Please try again.');
                return;
            }
        }

        $fullName = trim($validated['first_name']) . ' ' . trim($validated['last_name']);
        
        $user->name = $fullName;
        $user->email = $validated['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $oldImage = $user->image;

        if ($newPath) {
            $user->image = $newPath;
        }

        $user->save();

        // Remove old image file after successful save (avoid removing before save)
        if ($newPath && $oldImage) {
            try {
                if (Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }
            } catch (\Throwable $e) {
                // non-fatal — do not break user flow
            }
        }

        // Update currentImageUrl and reset temporary upload
        $this->currentImageUrl = $user->image ? Storage::url($user->image) : null;
        $this->photo = null;

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}