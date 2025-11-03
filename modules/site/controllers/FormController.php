<?php

namespace modules\site\controllers;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use craft\web\View;
use modules\site\helpers\MailNotificationHelper;
use yii\base\Exception;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class FormController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    /**
     * @throws MethodNotAllowedHttpException
     */
    public function actionSubmit(): Response
    {
        $this->requirePostRequest();

        // create a new entry in the `subscribers` channel and fill in their email within the `email` field
        // do not create duplicates, so first search for an existing entry with that email
        $email = \Craft::$app->getRequest()->getBodyParam('email');

        $existingEntry = Entry::find()
            ->section('subscribers')
            ->email($email)
            ->one();

        $existingDisabledEntry = Entry::find()
            ->section('subscribers')
            ->email($email)
            ->status(Element::STATUS_DISABLED)
            ->one();

        if ($existingDisabledEntry) {
            try {
                /** @var Entry $existingDisabledEntry */
                $existingDisabledEntry->enabled = true;
                if (!\Craft::$app->elements->saveElement($existingDisabledEntry)) {
                    return $this->asJson(['success' => false, 'message' => 'Could not re-enable entry.']);
                }
            } catch (ElementNotFoundException $e) {
                \Craft::error('Element not found: ' . $e->getMessage(), __METHOD__);
            } catch (Exception $e) {
                \Craft::error('Error saving entry: ' . $e->getMessage(), __METHOD__);
            } catch (\Throwable $e) {
                \Craft::error('Unexpected error: ' . $e->getMessage(), __METHOD__);
            }
            self::sendNewSubscriberEmail($email, $existingDisabledEntry->id);
            return $this->asJson(['success' => true, 'message' => 'Form submitted successfully.']);
        }

        if (!$existingEntry) {
            $entry = new Entry();
            $entry->sectionId = 7;
            $entry->typeId = 16;
            $entry->setFieldValue('email', $email);

            try {
                if (!\Craft::$app->elements->saveElement($entry)) {
                    return $this->asJson(['success' => false, 'message' => 'Could not save entry.']);
                }
            } catch (ElementNotFoundException $e) {
                \Craft::error('Element not found: ' . $e->getMessage(), __METHOD__);
            } catch (Exception $e) {
                \Craft::error('Error saving entry: ' . $e->getMessage(), __METHOD__);
            } catch (\Throwable $e) {
                \Craft::error('Unexpected error: ' . $e->getMessage(), __METHOD__);
            }
            $id = Entry::find()
                ->section('subscribers')
                ->email($email)
                ->one()
                ->id;
            self::sendNewSubscriberEmail($email, $id);
        }

        return $this->asJson(['success' => true, 'message' => 'Form submitted successfully.']);
    }

    private static function sendNewSubscriberEmail(string $email, $subscriberId): void
    {
        try {
            MailNotificationHelper::sendEmailNotification(
                $email,
                'Thank you for subscribing to Sumner Mission Stories',
                'site/_email/subscription-confirmation',
                [
                    'subscriberId' => $subscriberId,
                ]
            );
        } catch (InvalidConfigException|\craft\errors\InvalidFieldException $ex) {
            Craft::error('Error sending subscription confirmation email: ' . $ex->getMessage(), __METHOD__);
        }
    }

    public function actionUnsubscribe(): Response
    {
        $id = \Craft::$app->getRequest()->getQueryParam('id');

        if ($id === null) {
            return $this->renderTemplate('site/_email/unsubscribe-confirmation',
                [
                    'status' => 'error',
                    'message' => 'No email provided for unsubscription.',
                ],
                View::TEMPLATE_MODE_CP
            );
        }

        $subscriber = Entry::find()
            ->section('subscribers')
            ->id($id)
            ->one();

        if (!$subscriber) {
            return $this->renderTemplate('site/_email/unsubscribe-confirmation',
                [
                    'status' => 'error',
                    'message' => 'This subscriber not found',
                ],
                View::TEMPLATE_MODE_CP
            );
        }
        try {
            /** @var Entry $subscriber */
            $subscriber->enabled = false;
            if (!\Craft::$app->elements->saveElement($subscriber)) {
                \Craft::error('Could not disable entry: ' . $id, __METHOD__);
                return $this->renderTemplate('site/_email/unsubscribe-confirmation',
                    [
                        'status' => 'error',
                        'message' => 'Could not unsubscribe you at this time.',
                    ],
                    View::TEMPLATE_MODE_CP
                );
            }

            return $this->renderTemplate('site/_email/unsubscribe-confirmation',
                [
                    'status' => 'success',
                    'message' => 'You have been unsubscribed successfully.',
                ],
                View::TEMPLATE_MODE_CP
            );
        } catch (ElementNotFoundException $e) {
            \Craft::error('Element not found: ' . $e->getMessage(), __METHOD__);
            return $this->renderTemplate('site/_email/unsubscribe-confirmation',
                [
                    'status' => 'error',
                    'message' => 'Could not unsubscribe you at this time. ' . $e->getMessage(),
                ],
                View::TEMPLATE_MODE_CP
            );
        } catch (Exception $e) {
            \Craft::error('Error deleting entry: ' . $e->getMessage(), __METHOD__);
            return $this->renderTemplate('site/_email/unsubscribe-confirmation',
                [
                    'status' => 'error',
                    'message' => 'Could not unsubscribe you at this time. ' . $e->getMessage(),
                ],
                View::TEMPLATE_MODE_CP
            );
        } catch (\Throwable $e) {
            \Craft::error('Unexpected error: ' . $e->getMessage(), __METHOD__);
            return $this->renderTemplate('site/_email/unsubscribe-confirmation',
                [
                    'status' => 'error',
                    'message' => 'Could not unsubscribe you at this time. ' . $e->getMessage(),
                ],
                View::TEMPLATE_MODE_CP
            );
        }


    }
}