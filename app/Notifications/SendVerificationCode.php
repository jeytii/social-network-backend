<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
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
     * @param string  $token
     * @param bool  $prefersSMS
     * @return void
     */
    public function __construct(int $code, string $token, bool $prefersSMS)
    {
        $this->code = $code;
        $this->prefersSMS = $prefersSMS;
        $this->url = config('app.client_url') . "/verify/{$token}";
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
                    ->subject('Account verification')
                    ->markdown('email.verification', [
                        'name' => $notifiable->username,
                        'code' => $this->code,
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
        $minutesLeft = config('validation.expiration.verification');

        return (new NexmoMessage)
            ->content("Hi {$notifiable->username}! Thank you for using {$appName}. Redirect to the link {$this->url}, then enter the verification code {$this->code}. You only have {$minutesLeft} minutes to verify your account. Otherwise, request for another verification code.")
            ->from($appName);
    }
}
