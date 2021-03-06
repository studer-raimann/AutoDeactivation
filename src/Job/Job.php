<?php

namespace srag\Plugins\AutoDeactivation\Job;

use ilAutoDeactivationConfigGUI;
use ilDate;
use ilObjUser;
use srag\DIC\AutoDeactivation\Exception\DICException;
use srag\Notifications4Plugin\AutoDeactivation\Exception\Notifications4PluginException;
use srag\Notifications4Plugin\AutoDeactivation\Utils\Notifications4PluginTrait;
use srag\Plugins\AutoDeactivation\Config\ConfigFormGUI;
use srag\Plugins\AutoDeactivation\Notification\LastNotifiedRepository;
use srag\Plugins\AutoDeactivation\User\Repository as UserRepository;
use srag\Plugins\AutoDeactivation\Utils\AutoDeactivationTrait;
use ilAutoDeactivationPlugin;
use ilCronJob;
use ilCronJobResult;
use srag\DIC\AutoDeactivation\DICTrait;

/**
 * Class Job
 *
 * Generated by SrPluginGenerator v1.3.5
 *
 * @package srag\Plugins\AutoDeactivation\Job
 *
 * @author studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author studer + raimann ag - Team Custom 1 <support@studer-raimann.ch>
 */
class Job extends ilCronJob
{

    use DICTrait;
    use AutoDeactivationTrait;
    use Notifications4PluginTrait;
    const CRON_JOB_ID =  ilAutoDeactivationPlugin::PLUGIN_ID . "_cron";
    const PLUGIN_CLASS_NAME = ilAutoDeactivationPlugin::class;
    const LANG_MODULE = 'job';
    /**
     * @var UserRepository
     */
    protected $user_repository;


    /**
     * Job constructor
     */
    public function __construct()
    {
        $this->user_repository = new UserRepository(self::dic()->dic(), self::autoDeactivation()->config());
    }


    /**
     * @inheritDoc
     */
    public function getId() : string
    {
        return self::CRON_JOB_ID;
    }


    /**
     * @inheritDoc
     */
    public function getTitle() : string
    {
        return ilAutoDeactivationPlugin::PLUGIN_NAME;
    }


    /**
     * @inheritDoc
     * @throws DICException
     */
    public function getDescription() : string
    {
        return self::plugin()->translate('description', self::LANG_MODULE);
    }


    /**
     * @inheritDoc
     */
    public function hasAutoActivation() : bool
    {
        return true;
    }


    /**
     * @inheritDoc
     */
    public function hasFlexibleSchedule() : bool
    {
        return true;
    }


    /**
     * @inheritDoc
     */
    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_DAILY;
    }


    /**
     * @inheritDoc
     */
    public function getDefaultScheduleValue()/*:?int*/
    {
        return null;
    }


    /**
     * @return ilCronJobResult
     * @throws Notifications4PluginException
     */
    public function run() : ilCronJobResult
    {
        $result = new ilCronJobResult();

        $users_for_notification = $this->user_repository->getUsersForWarningNotification();
        $this->sendWarningNotifications($users_for_notification);

        $users_for_deactivation = $this->user_repository->getUsersToBeDeactivated();
        $this->deactivateAndNotify($users_for_deactivation);

        $users_notified = count($users_for_notification);
        $users_deactivated = count($users_for_deactivation);
        $message = 'users notified: ' . $users_notified . ', users deactivated: ' . $users_deactivated;

        self::dic()->logger()->root()->info($message);
        $result->setMessage($message);
        $result->setStatus(($users_notified + $users_deactivated == 0) ? ilCronJobResult::STATUS_NO_ACTION : ilCronJobResult::STATUS_OK);

        return $result;
    }


    /**
     * @param ilObjUser[] $users_for_notification
     *
     * @throws Notifications4PluginException
     */
    protected function sendWarningNotifications(array $users_for_notification)
    {
        $threshold_in_seconds = self::autoDeactivation()->config()->getValue(ConfigFormGUI::KEY_THRESHOLD_IN_DAYS) * 24 * 60 * 60;
        $notification = self::notifications4plugin()->notifications()->getNotificationByName(ilAutoDeactivationConfigGUI::NOTIFICATION_NAME_WARNING);
        $last_notified_repository = new LastNotifiedRepository();
        foreach ($users_for_notification as $ilObjUser) {
            $last_login_unix = strtotime($ilObjUser->getLastLogin() ?? $ilObjUser->getCreateDate());
            $inactive_for_days = round((time() - $last_login_unix) / 60 / 60 / 24);
            $deactivation_date = (new ilDate($last_login_unix + $threshold_in_seconds, IL_CAL_UNIX))->get(IL_CAL_DATE);
            $sender = self::notifications4plugin()->sender()->factory()->externalMail("", $ilObjUser->getEmail());
            self::notifications4plugin()->sender()->send(
                $sender,
                $notification,
                [
                    'user' => $ilObjUser,
                    'inactive_for_days' => $inactive_for_days,
                    'deactivation_date' => $deactivation_date,
                    'login_link' => ILIAS_HTTP_PATH
                ]
            );
            $last_notified_repository->userNotified($ilObjUser->getId());
        }
    }


    /**
     * @param ilObjUser[] $users_for_deactivation
     *
     * @throws Notifications4PluginException
     */
    protected function deactivateAndNotify(array $users_for_deactivation)
    {
        $bcc = explode(',', self::autoDeactivation()->config()->getValue(ConfigFormGUI::KEY_NOTIFICATION_EMAILS));
        $notification = self::notifications4plugin()->notifications()->getNotificationByName(ilAutoDeactivationConfigGUI::NOTIFICATION_NAME_DEACTIVATION);
        foreach ($users_for_deactivation as $ilObjUser) {
            $ilObjUser->setActive(false);
            $ilObjUser->update();

            $sender = self::notifications4plugin()->sender()->factory()->externalMail("", $ilObjUser->getEmail());
            if (!empty($bcc)) {
                $sender->setBcc($bcc);
            }
            self::notifications4plugin()->sender()->send(
                $sender,
                $notification,
                [
                    'user' => $ilObjUser,
                ]
            );
        }
    }
}
