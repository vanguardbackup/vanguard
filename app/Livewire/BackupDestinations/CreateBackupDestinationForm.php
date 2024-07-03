<?php

declare(strict_types=1);

namespace App\Livewire\BackupDestinations;

use App\Models\BackupDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Toaster;

class CreateBackupDestinationForm extends Component
{
    public string $label;

    public string $type = 'custom_s3';

    public ?string $s3AccessKey;

    public ?string $s3SecretKey;

    public ?string $s3BucketName;

    public bool $usePathStyleEndpoint = false;

    public ?string $customS3Region;

    public ?string $customS3Endpoint;

    public function submit(): RedirectResponse|Redirector
    {
        $this->validate([
            'label' => ['required', 'string'],
            'type' => ['required', 'string', 'in:custom_s3,s3'],
            's3AccessKey' => ['nullable', 'required_if:type,custom_s3,s3'],
            's3SecretKey' => ['nullable', 'required_if:type,custom_s3,s3'],
            's3BucketName' => ['nullable', 'required_if:type,custom_s3,s3'],
            'customS3Region' => ['nullable', 'required_if:type,s3'], // Not required for custom S3 connections
            'customS3Endpoint' => ['nullable', 'required_if:type,custom_s3'],
            'usePathStyleEndpoint' => ['boolean', 'required_if:type,s3,custom_s3'],
        ],
            [
                'label.required' => __('Please enter a label.'),
                'type.required' => __('Please select a type.'),
                'type.in' => __('Please select a valid type.'),
                's3AccessKey.required_if' => __('Please enter a valid S3 access key.'),
                's3SecretKey.required_if' => __('Please enter a valid S3 secret key.'),
                's3BucketName.required_if' => __('Please enter a valid S3 bucket name.'),
                'customS3Region.required_if' => __('Please enter a valid S3 region.'),
                'customS3Endpoint.required_if' => __('Please enter a valid S3 endpoint URL.'),
            ]);

        BackupDestination::create([
            'user_id' => Auth::id(),
            'label' => $this->label,
            'type' => $this->type,
            's3_access_key' => $this->s3AccessKey ?? null,
            's3_secret_key' => $this->s3SecretKey ?? null,
            's3_bucket_name' => $this->s3BucketName ?? null,
            'custom_s3_region' => $this->customS3Region ?? null,
            'custom_s3_endpoint' => $this->customS3Endpoint ?? null,
            'path_style_endpoint' => $this->usePathStyleEndpoint ?? false,
        ]);

        Toaster::success(__('Backup destination has been added.'));

        return Redirect::route('backup-destinations.index');
    }

    public function render(): View
    {
        return view('livewire.backup-destinations.create-backup-destination-form');
    }
}
