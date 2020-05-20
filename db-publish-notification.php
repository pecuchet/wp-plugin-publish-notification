<?php

/**
 * @link              https://dotburo.org
 * @since             1.0.0
 * @package           Db_Publish_Notification
 * @wordpress-plugin
 * Plugin Name:       Publish Notification
 * Plugin URI:        https://github.com/pecuchet/wp-plugin-publish-notification
 * Description:       Send an email notification to all users when a new post is published (code configurable).
 * Version:           1.0.0
 * Author:            dotburo
 * Author URI:        https://dotburo.org
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       db-publish-notification
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

add_action('transition_post_status', function ($new_status, $old_status, $post) {

    require __DIR__ . '/vendor/autoload.php';

    $config = (array)json_decode(file_get_contents(__DIR__ . '/config.json'), true);;

    $mailer = new Nette\Mail\SmtpMailer([
        'host' => $config['mail']['host'],
        'username' => $config['mail']['username'],
        'password' => $config['mail']['password'],
        'secure' => $config['mail']['encryption'],
        'port' => $config['mail']['port'],
    ]);

    $mail = new Nette\Mail\Message;

    (new dotburo\PublishNotification\SendMail($mailer, $mail, $config))->handler($new_status, $old_status, $post);

}, PHP_INT_MAX, 3);
