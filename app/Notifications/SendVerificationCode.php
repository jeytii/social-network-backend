<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendVerificationCode extends Notification implements ShouldQueue
{
    use Queueable;

    protected $code;

    protected $url;

    /**
     * Create a new notification instance.
     *
     * @param string  $code
     * @param string  $token
     * @return void
     */
    public function __construct(string $code, string $token)
    {
        $this->code = $code;
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
        return ['mail'];
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
                        'name' => $notifiable->name,
                        'code' => $this->code,
                        'url' => $this->url,
                    ]);
    }
}
