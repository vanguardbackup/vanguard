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
    public bool $success_notification = false;
    public bool $failed_notification = true;
    public ?string $additional_field_one = null;
    public ?string $additional_field_two = null;

    /** @var Collection<string, string> */
    public Collection $availableTypes;

    /** @var array<string, array<string, mixed>> */
    protected array $additionalFields = [
        NotificationStream::TYPE_PUSHOVER => [
            'additional_field_one' => [
                'label' => 'User Key',
                'rules' => ['required', 'string'],
                'error_message' => 'Please enter a valid Pushover User Key.',
            ],
        ],
    ];

    /** @var array<string, array<string, string>> */
    protected array $typeConfig = [
        NotificationStream::TYPE_DISCORD => [
            'label' => 'Webhook URL',
            'input_type' => 'url',
        ],
        NotificationStream::TYPE_SLACK => [
            'label' => 'Webhook URL',
            'input_type' => 'url',
        ],
        NotificationStream::TYPE_TEAMS => [
            'label' => 'Webhook URL',
            'input_type' => 'url',
        ],
        NotificationStream::TYPE_EMAIL => [
            'label' => 'Email Address',
            'input_type' => 'email',
        ],
        NotificationStream::TYPE_PUSHOVER => [
            'label' => 'API Token',
            'input_type' => 'text',
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in($this->availableTypes->keys())],
            'success_notification' => ['nullable', 'boolean'],
            'failed_notification' => ['nullable', 'boolean'],
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

        // Add dynamic rules for additional fields
        if (isset($this->additionalFields[$this->type])) {
            foreach ($this->additionalFields[$this->type] as $field => $config) {
                $rules[$field] = $config['rules'];
            }
        } else {
            $rules['additional_field_one'] = ['nullable'];
            $rules['additional_field_two'] = ['nullable'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'label.required' => __('Please enter a label.'),
            'label.max' => __('The label must not exceed 255 characters.'),
            'type.required' => __('Please select a notification type.'),
            'type.in' => __('Please select a valid notification type.'),
            'value.required' => __('Please enter a value for the selected notification type.'),
            'success_notification.boolean' => __('The notification status must be true or false.'),
            'failed_notification.boolean' => __('The notification status must be true or false.'),
        ];

        if (isset($this->additionalFields[$this->type])) {
            foreach ($this->additionalFields[$this->type] as $field => $config) {
                $messages[$field . '.required'] = __($config['error_message']);
            }
        }

        return $messages;
    }

    public function initialize(): void
    {
        $this->availableTypes = collect([
            NotificationStream::TYPE_DISCORD => __('Discord Webhook'),
            NotificationStream::TYPE_SLACK => __('Slack Webhook'),
            NotificationStream::TYPE_TEAMS => __('Microsoft Teams Webhook'),
            NotificationStream::TYPE_EMAIL => __('Email'),
            NotificationStream::TYPE_PUSHOVER => __('Pushover'),
        ]);
    }

    public function setNotificationStream(NotificationStream $notificationStream): void
    {
        $this->label = $notificationStream->getAttribute('label');
        $this->type = $notificationStream->getAttribute('type');
        $this->value = $notificationStream->getAttribute('value');
        $this->success_notification = (bool) $notificationStream->getAttribute('receive_successful_backup_notifications');
        $this->failed_notification = (bool) $notificationStream->getAttribute('receive_failed_backup_notifications');
        $this->additional_field_one = $notificationStream->getAttribute('additional_field_one');
        $this->additional_field_two = $notificationStream->getAttribute('additional_field_two');
    }

    /**
     * Get the configuration for additional fields based on the current type.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAdditionalFieldsConfig(): array
    {
        return $this->additionalFields[$this->type] ?? [];
    }

    public function getValueLabel(): string
    {
        return $this->typeConfig[$this->type]['label'] ?? __('Value');
    }

    public function getValueInputType(): string
    {
        return $this->typeConfig[$this->type]['input_type'] ?? 'text';
    }

    /**
     * @return array<int, string>|string
     */
    protected function getValueValidationRule(): array|string
    {
        return match ($this->type) {
            NotificationStream::TYPE_DISCORD => ['url', 'regex:/^https:\/\/discord\.com\/api\/webhooks\//'],
            NotificationStream::TYPE_SLACK => ['url', 'regex:/^https:\/\/hooks\.slack\.com\/services\//'],
            NotificationStream::TYPE_TEAMS => ['url', 'regex:/^https:\/\/.*\.webhook\.office\.com\/webhookb2\/.+/i'],
            NotificationStream::TYPE_PUSHOVER => ['required', 'string'],
            NotificationStream::TYPE_EMAIL => ['email'],
            default => 'string',
        };
    }

    protected function getValueErrorMessage(): string
    {
        return match ($this->type) {
            NotificationStream::TYPE_DISCORD => __('Please enter a valid Discord webhook URL.'),
            NotificationStream::TYPE_SLACK => __('Please enter a valid Slack webhook URL.'),
            NotificationStream::TYPE_TEAMS => __('Please enter a valid Microsoft Teams Webhook URL.'),
            NotificationStream::TYPE_EMAIL => __('Please enter a valid email address.'),
            NotificationStream::TYPE_PUSHOVER => __('Please enter a valid Pushover API Token.'),
            default => __('Please enter a valid value for the selected notification type.'),
        };
    }
}
