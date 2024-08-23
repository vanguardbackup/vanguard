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

/**
 * Livewire component for creating a new backup destination.
 *
 * This component handles the form submission and validation for creating
 * various types of backup destinations, including custom S3, S3, and local.
 */
class CreateBackupDestinationForm extends Component
{
    public string $label;
    public string $type = 'custom_s3';
    public ?string $s3AccessKey = null;
    public ?string $s3SecretKey = null;
    public ?string $s3BucketName = null;
    public bool $usePathStyleEndpoint = true;
    public ?string $customS3Region = null;
    public ?string $customS3Endpoint = null;

    /**
     * Handle the form submission.
     *
     * Validates the input, creates a new BackupDestination, and redirects to the index page.
     */
    public function submit(): RedirectResponse|Redirector
    {
        $this->validate([
            'label' => ['required', 'string'],
            'type' => ['required', 'string', 'in:custom_s3,s3,local,digitalocean_spaces'],
            's3AccessKey' => ['nullable', 'required_if:type,custom_s3,s3,digitalocean_spaces'],
            's3SecretKey' => ['nullable', 'required_if:type,custom_s3,s3,digitalocean_spaces'],
            's3BucketName' => ['nullable', 'required_if:type,custom_s3,s3,digitalocean_spaces'],
            'customS3Region' => ['nullable', 'required_if:type,s3'], // Not required for custom S3 connections or DO spaces.
            'customS3Endpoint' => ['nullable', 'required_if:type,custom_s3,digitalocean_spaces'],
            'usePathStyleEndpoint' => ['boolean', 'required_if:type,s3,custom_s3,digitalocean_spaces'],
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

        Toaster::success('Backup destination has been added.');

        return Redirect::route('backup-destinations.index');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.backup-destinations.create-backup-destination-form');
    }
}
