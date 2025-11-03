<?php

namespace modules\site;

use Craft;
use craft\base\Element;
use craft\base\Event;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use craft\events\ElementEvent;
use craft\events\ModelEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\ElementHelper;
use craft\mail\Message;
use craft\web\View;
use modules\site\helpers\MailNotificationHelper;
use yii\base\InvalidConfigException;
use yii\base\Module as BaseModule;

/**
 * Site module
 *
 * @method static Site getInstance()
 */
class Site extends BaseModule
{
    public function init(): void
    {
        Craft::setAlias('@modules/site', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'modules\\site\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\site\\controllers';
        }

        parent::init();

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/5.x/extend/events.html to get started)

        // Base template directory
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e) {
                if (is_dir($baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                    $e->roots[$this->id] = $baseDir;
                }
            }
        );

        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e) {
                if (is_dir($baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                    $e->roots[$this->id] = $baseDir;
                }
            }
        );

        // send an email with a link to the latest blog entry when a new blog post is published for the first time
        Event::on(
            Entry::class,
            Element::EVENT_AFTER_SAVE,
            static function(ModelEvent $e) {
                /* @var Entry $entry */
                $entry = $e->sender;

                if (ElementHelper::isDraftOrRevision($entry)) {
                    // We don’t care about these, so just skip it!
                    return;
                }
                $isNew = $e->isNew;
                // It's a new entry!
                if ($isNew && $entry->section->handle === 'blog') {
                    // It's a blog entry!
                    // Send the email notification
                    $module = Site::getInstance();
                    try {
                        $subscribers = Entry::find()
                            ->section('subscribers')
                            ->all();
                        foreach ($subscribers as $subscriber) {
                            /** @var Entry $subscriber */
                            if (empty($subscriber->email)) {
                                continue;
                            }
                            MailNotificationHelper::sendEmailNotification($subscriber->getFieldValue('email'), 'New Sumner Mission Story Published: ' . $entry->title,'site/_email/post-notification', [
                                'entry' => $entry,
                                'subscriberId' => $subscriber->id,
                            ]);
                        }

                    } catch (InvalidConfigException|InvalidFieldException $ex) {
                        Craft::error('Error sending email notification: ' . $ex->getMessage(), __METHOD__);
                    }
                }
            }
        );

    }


}
