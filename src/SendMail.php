<?php

namespace dotburo\PublishNotification;

use Nette\Mail\Mailer;
use Nette\Mail\Message;

/**
 * Send the publish notification.
 *
 * @copyleft 2020 dotburo
 * @author dotburo <code@dotburo.org>
 */
class SendMail
{
    /** @var string */
    private const SENT_FLAG = 'db_publish_notification_sent';

    /** @var array */
    private $config;

    /** @var Mailer */
    private $mailer;

    /** @var Message */
    private $message;

    /**
     * SendMail constructor.
     * @param Mailer $mailer
     * @param Message $message
     * @param array $config
     */
    public function __construct(Mailer $mailer, Message $message, array $config)
    {
        $this->mailer = $mailer;

        $this->message = $message;

        $this->config = $config;
    }

    /**
     * WordPress action hook handler.
     * @param string $new_status
     * @param string $old_status
     * @param $post
     */
    public function handler(string $new_status, string $old_status, $post)
    {
        $allowed = in_array($post->post_type, $this->config['post_types']);

        $isNewlyPublished = 'publish' === $new_status && 'publish' !== $old_status;

        if ($allowed && $isNewlyPublished) {
            $sent = (int)get_post_meta($post->ID, self::SENT_FLAG, true);

            if (!$sent) {
                $recipients = $this->getRecipients();

                $this->sendMessage($post->post_title, get_permalink($post->ID), $recipients);

                update_post_meta($post->ID, self::SENT_FLAG, time());
            }
        }
    }

    /**
     * Get the recipients.
     * @return array
     */
    protected function getRecipients(): array
    {
        $recipients = array_map(function ($user) {
            if (in_array($user->user_email, $this->config['exclude_addresses'])) return null;
            return $this->config['replace_addresses'][$user->user_email] ?? $user->user_email;
        }, get_users());

        return array_filter($recipients);
    }

    /**
     * Send out the mail.
     * @param string $title
     * @param string $url
     * @param array $recipients
     */
    protected function sendMessage(string $title, string $url, array $recipients): void
    {
        $from = "{$this->config['mail']['from_name']} <{$this->config['mail']['from_addr']}>";

        $this->message
            ->setFrom($from)
            ->setSubject($title)
            ->setHtmlBody($this->renderTemplate([
                'siteName' => get_bloginfo('name'),
                'title' => $title,
                'url' => $url
            ]));

        foreach ($recipients as $email) {
            $this->message->addBcc($email);
        }

        $this->mailer->send($this->message);
    }

    /**
     * Make the mail body.
     * @param array $params
     * @return string
     */
    protected function renderTemplate(array $params): string
    {
        $body = file_get_contents(__DIR__ . '/template.html');

        foreach ($params as $name => $value) {
            $body = str_replace("|*$name*|", $value, $body);
        }

        return $body;
    }
}
