<div class="container mx-auto px-4 py-12 flex items-center justify-center">
    <div class="card w-full max-w-2xl glass-card shadow-2xl relative overflow-hidden">
        <div class="top-accent-center"></div>
        
        <div class="card-body p-8 sm:p-10">
            <h2 class="card-title text-3xl font-bold mb-2 text-white">Edit Profile</h2>
            <p class="text-gray-400 mb-8">Update your personal information and preferences.</p>

            <!-- Success Message -->
            @if (session()->has('message'))
                <div role="alert" class="alert alert-success mb-6 bg-green-500/10 border-green-500/20 text-green-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('message') }}</span>
                </div>
            @endif

            <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Profile Photo (Custom UI preserved as requested) -->
                <div class="form-control md:col-span-2">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Profile Photo</span></label>
                    <div class="flex flex-col sm:flex-row items-center gap-6 bg-slate-800/30 p-6 rounded-2xl border border-white/5">
                        <div class="avatar">
                            <div class="w-16 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                                @if ($profile_image)
                                    <img src="{{ $profile_image->temporaryUrl() }}" alt="Preview" />
                                @elseif ($current_profile_image_url)
                                    <img src="{{ Storage::url($current_profile_image_url) }}" alt="Current" />
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center">
                                        <span class="text-xl font-bold text-white">{{ substr($display_name ?? 'U', 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col gap-3 w-full sm:w-auto">
                            <div class="flex gap-3">
                                <label for="profile-image-input" class="btn btn-sm btn-primary bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white">
                                    Change Photo
                                </label>
                                <input type="file" wire:model="profile_image" accept="image/*" class="hidden" id="profile-image-input">
                                
                                @if ($current_profile_image_url)
                                    <button type="button" wire:click="removeProfileImage" class="btn btn-sm btn-outline btn-error">
                                        Remove
                                    </button>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">
                                JPG, PNG or WebP. Max 2MB.
                                @error('profile_image') <span class="text-red-400 block mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div wire:loading wire:target="profile_image" class="text-xs text-cyan-400 animate-pulse">
                                Uploading...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Display Name -->
                <div class="form-control md:col-span-2">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Display Name</span></label>
                    <input type="text" wire:model="display_name" placeholder="e.g. CosmicExplorer"
                           class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400" />
                    @error('display_name') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Bio -->
                <div class="form-control md:col-span-2">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Bio</span></label>
                    <textarea wire:model="bio" class="textarea textarea-bordered h-28 bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400"
                              placeholder="Tell us about yourself..."></textarea>
                    <div class="label">
                        <span class="label-text-alt text-gray-500">{{ strlen($bio ?? '') }}/500</span>
                        @error('bio') <span class="label-text-alt text-red-400">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Interests -->
                <div class="form-control md:col-span-2">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Interests</span></label>
                    <div class="flex gap-2 mb-3">
                        <input type="text" wire:model="newInterest" wire:keydown.enter.prevent="addInterest"
                               placeholder="Add interest (Enter)"
                               class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400" />
                        <button type="button" wire:click="addInterest" class="btn btn-primary bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white">Add</button>
                    </div>
                    
                    @if(count($interests) > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($interests as $index => $interest)
                                <div class="badge badge-lg gap-2 bg-purple-500/20 text-purple-300 border-purple-500/30 p-3">
                                    {{ $interest }}
                                    <button type="button" wire:click="removeInterest({{ $index }})" class="hover:text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-4 h-4 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="label">
                        <span class="label-text-alt text-gray-500">{{ count($interests) }}/10 interests</span>
                        @error('interests') <span class="label-text-alt text-red-400">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Location Name -->
                <div class="form-control md:col-span-2">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Location Name</span></label>
                    <input type="text" wire:model="location_name" placeholder="e.g. San Francisco, CA"
                           class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400" />
                    @error('location_name') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Coordinates -->
                <div class="form-control">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Latitude</span></label>
                    <input type="number" step="any" wire:model="latitude" placeholder="37.7749"
                           class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400" />
                    @error('latitude') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Longitude</span></label>
                    <input type="number" step="any" wire:model="longitude" placeholder="-122.4194"
                           class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400" />
                    @error('longitude') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Buttons -->
                <div class="md:col-span-2 flex justify-end gap-4 mt-6">
                    <a href="{{ route('profile.show') }}" class="btn btn-ghost text-gray-400 hover:text-white hover:bg-white/10">Cancel</a>
                    <button type="submit" class="btn btn-primary bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white shadow-lg shadow-purple-500/20 hover:scale-105 transition-transform">
                        <span wire:loading.remove wire:target="save">Save Changes</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>