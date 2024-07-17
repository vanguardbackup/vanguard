<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams\Forms;

use App\Models\NotificationStream;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Form;

class NotificationStreamForm extends Form
{
    public string $label = '';
    public string $type = NotificationStream::TYPE_EMAIL;
    public string $value = '';

    /** @var Collection<string, string> */
    public Collection $availableTypes;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in($this->availableTypes->keys())],
            'value' => [
                'required',
                function ($attribute, $value, $fail): void {
                    $rules = $this->getValueValidationRule();
                    Log::debug('Validating value', ['type' => $this->type, 'value' => $value, 'rules' => $rules]);

                    $validator = Validator::make(['value' => $value], ['value' => $rules]);

                    if ($validator->fails()) {
                        Log::debug('Validation failed', ['errors' => $validator->errors()->toArray()]);
                        $fail($this->getValueErrorMessage());
                    } else {
                        Log::debug('Validation passed');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => __('Please enter a label.'),
            'label.max' => __('The label must not exceed 255 characters.'),
            'type.required' => __('Please select a notification type.'),
            'type.in' => __('Please select a valid notification type.'),
            'value.required' => __('Please enter a value for the selected notification type.'),
        ];
    }

    public function initialize(): void
    {
        $this->availableTypes = collect([
            NotificationStream::TYPE_DISCORD => __('Discord Webhook'),
            NotificationStream::TYPE_SLACK => __('Slack Webhook'),
            NotificationStream::TYPE_EMAIL => __('Email'),
        ]);
    }

    public function setNotificationStream(NotificationStream $notificationStream): void
    {
        $this->label = $notificationStream->getAttribute('label');
        $this->type = $notificationStream->getAttribute('type');
        $this->value = $notificationStream->getAttribute('value');
    }

    /**
     * @return array<int, string>|string
     */
    protected function getValueValidationRule(): array|string
    {
        return match ($this->type) {
            NotificationStream::TYPE_DISCORD => ['url', 'regex:/^https:\/\/discord\.com\/api\/webhooks\//'],
            NotificationStream::TYPE_SLACK => ['url', 'regex:/^https:\/\/hooks\.slack\.com\/services\//'],
            NotificationStream::TYPE_EMAIL => 'email',
            default => 'string',
        };
    }

    protected function getValueErrorMessage(): string
    {
        return match ($this->type) {
            NotificationStream::TYPE_DISCORD => __('Please enter a Discord webhook URL.'),
            NotificationStream::TYPE_SLACK => __('Please enter a Slack webhook URL.'),
            NotificationStream::TYPE_EMAIL => __('Please enter an email address.'),
            default => __('Please enter a valid value for the selected notification type.'),
        };
    }
}
