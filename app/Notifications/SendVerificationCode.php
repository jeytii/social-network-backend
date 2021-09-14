<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
            ->content("Hi {$notifiable->username}! Thank you for using {$appName}. Your verification code is {$this->code}.")
            ->from($appName);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
