<?php

namespace dwy\FacebookConversion\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use dwy\FacebookConversion\models\Event;
use dwy\FacebookConversion\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EventsController extends Controller
{
    public function actionModal(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();
        $this->requirePermission(Plugin::PERMISSION_VIEW_EVENT_LOGS);

        $request = Craft::$app->getRequest();
        $id = (int) $request->getRequiredParam('id');
        $type = (string) $request->getRequiredParam('type');

        $event = Event::find()->where(['id' => $id])->one();
        if (!$event) {
            throw new NotFoundHttpException('Event not found.');
        }

        $title = match ($type) {
            'event-data' => Craft::t('facebook-conversion', 'Event Data'),
            'event-response' => Craft::t('facebook-conversion', 'Response'),
            'event-error' => Craft::t('facebook-conversion', 'Error'),
            default => throw new BadRequestHttpException('Invalid modal type.'),
        };

        $payload = match ($type) {
            'event-data' => Json::decodeIfJson($event->event_data),
            'event-response' => Json::decodeIfJson($event->response),
            'event-error' => $event->error,
            default => null,
        };

        $testEventCode = null;
        if ($type === 'event-data') {
            $testEventCode = Plugin::getInstance()->getSettings()->getTestEventCode();
            if ($testEventCode === '') {
                $testEventCode = null;
            }
        }

        return $this->asJson([
            'id' => $event->id,
            'type' => $type,
            'title' => $title,
            'payload' => $payload,
            'testEventCode' => $testEventCode,
        ]);
    }
}

