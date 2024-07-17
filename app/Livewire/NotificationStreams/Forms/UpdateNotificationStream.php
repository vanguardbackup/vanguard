<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams\Forms;

use App\Models\NotificationStream;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

class UpdateNotificationStream extends Component
{
    use AuthorizesRequests;

    public NotificationStream $notificationStream;

    public string $label;
    public string $type;
    public string $value;

    public function mount(NotificationStream $notificationStream): void
    {
        $this->authorize('update', $notificationStream);

        $this->notificationStream = $notificationStream;
        $this->label = $notificationStream->getAttribute('label');
        $this->type = $notificationStream->getAttribute('type');
        $this->value = $notificationStream->getAttribute('value');
    }

    public function submit(): RedirectResponse|Redirector
    {
        $this->authorize('update', $this->notificationStream);

        $this->validateNotification();

        $this->notificationStream->update([
            'label' => $this->label,
            'type' => $this->type,
            'value' => $this->value,
        ]);

        Toaster::success(__('Notification stream has been saved.'));

        return Redirect::route('notification-streams.index');
    }

    public function validateNotification(): void
    {
        $validator = Validator::make(
            [
                'label' => $this->label,
                'type' => $this->type,
                'value' => $this->value,
            ],
            [
                'label' => ['required', 'string', 'max:255'],
                'type' => ['required', 'string', Rule::in(['discord_webhook', 'slack_webhook', 'email'])],
                'value' => [
                    'required',
                    function ($attribute, $value, $fail): void {
                        if (empty($value)) {
                            return; // Let the 'required' rule handle empty values
                        }

                        $validation = match ($this->type) {
                            'discord_webhook' => preg_match('/^https:\/\/discord\.com\/api\/webhooks\//', $value),
                            'slack_webhook' => preg_match('/^https:\/\/hooks\.slack\.com\/services\//', $value),
                            'email' => filter_var($value, FILTER_VALIDATE_EMAIL),
                            default => false,
                        };

                        if (! $validation) {
                            $errorMessage = match ($this->type) {
                                'discord_webhook' => __('Please enter a valid Discord webhook URL.'),
                                'slack_webhook' => __('Please enter a valid Slack webhook URL.'),
                                'email' => __('Please enter a valid email address.'),
                                default => __('Please enter a valid value for the selected notification type.'),
                            };
                            $fail($errorMessage);
                        }
                    },
                ],
            ],
            [
                'label.required' => __('Please enter a label.'),
                'label.max' => __('The label must not exceed 255 characters.'),
                'type.required' => __('Please select a notification type.'),
                'type.in' => __('Please select a valid notification type.'),
                'value.required' => __('Please enter a value for the selected notification type.'),
            ]
        );

        $validator->after(function ($validator): void {
            if ($validator->errors()->has('value')) {
                $messages = $validator->errors()->get('value');
                if (count($messages) === 1 && $messages[0] === __('Please enter a value for the selected notification type.')) {
                    $newMessage = match ($this->type) {
                        'discord_webhook' => __('Please enter a Discord webhook URL.'),
                        'slack_webhook' => __('Please enter a Slack webhook URL.'),
                        'email' => __('Please enter an email address.'),
                        default => __('Please enter a value for the selected notification type.'),
                    };
                    $validator->errors()->forget('value');
                    $validator->errors()->add('value', $newMessage);
                }
            }
        });

        $validator->validate();
    }

    public function render(): View
    {
        return view('livewire.notification-streams.forms.update-notification-stream', [
            'notificationStream' => $this->notificationStream,
        ]);
    }
}
