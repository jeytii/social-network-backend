<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Route;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\{MailMessage, NexmoMessage};
use Illuminate\Contracts\Queue\ShouldQueue;

class SendVerificationCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param int  $code
     * @param bool  $prefersSMS
     * @return void
     */
    public function __construct(int $code, bool $prefersSMS)
    {
        $this->code = $code;
        $this->prefersSMS = $prefersSMS;
        $this->routeName = Route::currentRouteName();
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
        if (in_array($this->routeName, ['auth.register', 'auth.verify.resend'])) {
            $subject = 'Account verification';
        }

        if ($this->routeName === 'settings.request-update.username') {
            $subject = "Request to update username";
        }

        return (new MailMessage) 
                    ->from(config('app.email'))
                    ->subject($subject)
                    ->markdown('email.verification', [
                        'name' => $notifiable->username,
                        'code' => $this->code,
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

        if (in_array($this->routeName, ['auth.register', 'auth.verify.resend'])) {
            $sentence = "Thank you for using {$appName}. The verification code is {$this->code}.";
        }

        if ($this->routeName === 'settings.request-update.username') {
            $sentence = "Thank you for using {$appName}. You have requested to update your username. The verification code is {$this->code}. You only have 30 minutes to update your username with it.";
        }

        return (new NexmoMessage)
            ->content("Hi {$notifiable->username}! {$sentence}")
            ->from($appName);
    }
}
