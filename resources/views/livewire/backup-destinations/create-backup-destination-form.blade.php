<div>
    <x-form-wrapper>
        <x-slot name="title">
            {{ __('Add Backup Destination') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Create a new backup destination.') }}
        </x-slot>
        <x-slot name="icon">
            hugeicons-global
        </x-slot>
        <form wire:submit="submit">
            <div class="mt-4 flex flex-col md:flex-row md:space-x-6 space-y-4 md:space-y-0">
                <div class="w-full md:w-3/6">
                    <x-input-label for="label" :value="__('Label')"/>
                    <x-text-input id="label" class="block mt-1 w-full" type="text" wire:model="label" name="label"
                                  autofocus/>
                    <x-input-error :messages="$errors->get('label')" class="mt-2"/>
                </div>
                <div class="w-full md:w-3/6">
                    <x-input-label for="type" :value="__('Type')"/>
                    <x-select id="type" class="block mt-1 w-full" wire:model.live="type" name="type">
                        <option value="s3">{{ __('Amazon S3') }}</option>
                        <option value="custom_s3">{{ __('Custom S3') }}</option>
                        <option value="digitalocean_spaces">{{ __('DigitalOcean S3 Spaces') }}</option>
                        <option value="local">{{ __('Local') }}</option>
                    </x-select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2"/>
                </div>
            </div>
            @if ($type === \App\Models\BackupDestination::TYPE_CUSTOM_S3 || $type === \App\Models\BackupDestination::TYPE_S3 || $type === \App\Models\BackupDestination::TYPE_DO_SPACES)
                <div class="mt-4 flex flex-col md:flex-row md:space-x-6 space-y-4 md:space-y-0">
                    <div class="w-full md:w-3/6">
                        <x-input-label for="s3AccessKey" :value="__('Access Key')"/>
                        <x-text-input id="s3AccessKey" class="block mt-1 w-full" type="text" wire:model="s3AccessKey"
                                      name="s3AccessKey"/>
                        <x-input-error :messages="$errors->get('s3AccessKey')" class="mt-2"/>
                    </div>
                    <div class="w-full md:w-3/6">
                        <x-input-label for="s3SecretKey" :value="__('Secret Key')"/>
                        <x-text-input id="s3SecretKey" class="block mt-1 w-full" type="text" wire:model="s3SecretKey"
                                      name="s3SecretKey"/>
                        <x-input-error :messages="$errors->get('s3SecretKey')" class="mt-2"/>
                    </div>
                </div>
                <div class="mt-4 flex flex-col md:flex-row md:space-x-6 space-y-4 md:space-y-0">
                    <div class="w-full md:w-3/6">
                        <x-input-label for="s3BucketName" :value="__('Bucket Name')"/>
                        <x-text-input id="s3BucketName" class="block mt-1 w-full" type="text" wire:model="s3BucketName"
                                      name="s3BucketName"/>
                        <x-input-error :messages="$errors->get('s3BucketName')" class="mt-2"/>
                    </div>
                    <div class="w-full md:w-3/6">
                        <x-input-label for="customS3Region" :value="__('Region')"/>
                        <x-text-input id="customS3Region" class="block mt-1 w-full" type="text"
                                      wire:model="customS3Region"
                                      name="customS3Region"/>
                        <x-input-error :messages="$errors->get('customS3Region')" class="mt-2"/>
                        <x-input-explain>
                            {{ __('The region where the bucket is located. This is optional for Custom S3s.') }}
                        </x-input-explain>
                    </div>
                </div>
            @endif
            @if ($type === \App\Models\BackupDestination::TYPE_CUSTOM_S3 || $type === \App\Models\BackupDestination::TYPE_DO_SPACES)
                <div class="mt-4">
                    <x-input-label for="customS3Endpoint" :value="__('Endpoint')"/>
                    <x-text-input id="customS3Endpoint" class="block mt-1 w-full" type="text"
                                  wire:model="customS3Endpoint"
                                  name="customS3Endpoint"/>
                    <x-input-error :messages="$errors->get('customS3Endpoint')" class="mt-2"/>
                </div>
            @endif
            @if ($type === \App\Models\BackupDestination::TYPE_CUSTOM_S3 || $type === \App\Models\BackupDestination::TYPE_S3 || $type === \App\Models\BackupDestination::TYPE_DO_SPACES)
                <div class="mt-4">
                    <x-input-label for="usePathStyleEndpoint" :value="__('Use Path Style Endpoint')"/>
                    <x-toggle
                        name="usePathStyleEndpoint"
                        model="usePathStyleEndpoint"
                    />
                    <x-input-error :messages="$errors->get('usePathStyleEndpoint')" class="mt-2"/>
                    <x-input-explain>
                        {{ __('This will append the bucket name to the URL instead of adding it as a subdomain.') }}
                    </x-input-explain>
                </div>
            @endif
            <div class="mt-6 max-w-3xl mx-auto">
                <div class="flex flex-col sm:flex-row sm:space-x-5 space-y-4 sm:space-y-0">
                    <div class="w-full sm:w-4/6">
                        <x-primary-button type="submit" class="w-full justify-center" centered action="submit">
                            {{ __('Save') }}
                        </x-primary-button>
                    </div>
                    <div class="w-full sm:w-2/6">
                        <a href="{{ route('backup-destinations.index') }}" wire:navigate class="block">
                            <x-secondary-button type="button" class="w-full justify-center" centered>
                                {{ __('Cancel') }}
                            </x-secondary-button>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </x-form-wrapper>
</div>
