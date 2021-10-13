<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;

class ResetPassword extends Notification
{
    use Queueable;

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
                        'name' => $notifiable->username,
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

        return (new NexmoMessage)
            ->content("Hi {$notifiable->username}! Thank you for using {$appName}. The password reset link is {$this->url}.")
            ->from($appName);
    }
}
