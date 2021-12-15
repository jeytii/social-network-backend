<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\{MailMessage, NexmoMessage};

class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public $url;

    public $prefersSMS;

    /**
     * Create a new notification instance.
     *
     * @param string  $url
     * @param bool  $prefersSMS
     * @return void
     */
    public function __construct(string $url, bool $prefersSMS)
    {
        $this->url = $url;
        $this->prefersSMS = $prefersSMS;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $this->prefersSMS ? ['nexmo'] : ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->from(config('app.email'))
                    ->subject('Request to reset password')
                    ->markdown('password.reset', [
                        'name' => $notifiable->name,
                        'url' => $this->url,
                    ]);
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\NexmoMessage
     */
    public function toNexmo($notifiable)
    {
        $appName = config('app.name');
        $minutesLeft = config('validation.expiration.password_reset');

        return (new NexmoMessage)
            ->content("Hi {$notifiable->name}! Thank you for using {$appName}. Your password-reset link is {$this->url}. You only have {$minutesLeft} minutes to reset your password.")
            ->from($appName);
    }
}
