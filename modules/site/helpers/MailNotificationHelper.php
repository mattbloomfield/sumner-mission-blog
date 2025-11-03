<?php

namespace modules\site\helpers;

use Craft;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use craft\mail\Message;
use craft\web\View;
use yii\base\InvalidConfigException;

class MailNotificationHelper {
    /**
     * @throws InvalidConfigException
     * @throws InvalidFieldException
     */
    public static function sendEmailNotification(string|array $to, string $subject, string $template, array $templateVariables = []): void
    {
        $subscribers = Entry::find()
            ->section('subscribers')
            ->all();
        $successes = 0;
        $failures = 0;
        foreach ($subscribers as $subscriber) {
            /** @var Entry $subscriber */
            $email = $subscriber->getFieldValue('email');
            if (!$email) {
                continue;
            }
            $message = new Message();
            $message->setTo($to);
            $message->setFrom([
                'hello@mail.sumnermission.org' => 'Another Side of Heaven',
            ]);
            $message->setSubject($subject);
            $emailHtml = Craft::$app->getView()->renderTemplate($template,
                $templateVariables,
                View::TEMPLATE_MODE_CP
            );
            $message->setHtmlBody($emailHtml);
            $success = Craft::$app->getMailer()->send($message);

            // If unsuccessful, log error and bail
            if (!$success) {
                Craft::warning("Unable to send the email using Craft's native email handling.", __METHOD__);
                Craft::error("Check your general email settings within Craft.",__METHOD__);
                $successes++;
            } else {
                Craft::info('Email notification sent for subject: ' . $subject, __METHOD__);
                $failures++;
            }
        }
        Craft::info("Email notifications sent: $successes successful, $failures failed.", __METHOD__);
    }
}