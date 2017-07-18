<?php

namespace humhub\modules\calendar;

use Yii;
use yii\helpers\Url;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\components\ContentContainerActiveRecord;

class Module extends \humhub\modules\content\components\ContentContainerModule
{

    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            Space::className(),
            User::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function disable()
    {
        foreach (CalendarEntry::find()->all() as $entry) {
            $entry->delete();
        }

        parent::disable();
    }

    /**
     * @inheritdoc
     */
    public function disableContentContainer(ContentContainerActiveRecord $container)
    {
        parent::disableContentContainer($container);
        foreach (CalendarEntry::find()->contentContainer($container)->all() as $entry) {
            $entry->delete();
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        if ($container instanceof Space) {
            return Yii::t('CalendarModule.base', 'Adds an event calendar to this space.');
        } elseif ($container instanceof User) {
            return Yii::t('CalendarModule.base', 'Adds an calendar for private or public events to your profile and mainmenu.');
        }
    }

    public function getContentContainerConfigUrl(ContentContainerActiveRecord $container)
    {
        return $container->createUrl('/calendar/entry/config');
    }

    public function getConfigUrl()
    {
        return Url::to([
            '/calendar/config'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPermissions($contentContainer = null)
    {
        if ($contentContainer !== null) {
            return [
                new permissions\CreateEntry(),
                new permissions\ManageEntry(),
            ];
        }
        return [];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // Fix prior 1.2.1 without set formatter timeZone
        // https://github.com/humhub/humhub/commit/3a06a3816131c3c10659b65e70422a8b8bdca15c#diff-6245cc1612ecb552c18a2e5a1d9bbca2c
        if (empty(Yii::$app->formatter->timeZone)) {
            Yii::$app->formatter->timeZone = Yii::$app->timeZone;
        }
        return parent::beforeAction($action);
    }

}
