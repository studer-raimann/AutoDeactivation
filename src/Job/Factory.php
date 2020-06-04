<?php

namespace srag\Plugins\AutoDeactivation\Job;

use srag\Plugins\AutoDeactivation\Utils\AutoDeactivationTrait;
use ilCronJob;
use srag\DIC\AutoDeactivation\DICTrait;

/**
 * Class Factory
 *
 * Generated by SrPluginGenerator v1.3.5
 *
 * @package srag\Plugins\AutoDeactivation\Job
 *
 * @author studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author studer + raimann ag - Team Custom 1 <support@studer-raimann.ch>
 */
final class Factory
{

    use DICTrait;
    use AutoDeactivationTrait;
    const PLUGIN_CLASS_NAME = ilAutoDeactivationPlugin::class;
    /**
     * @var self
     */
    protected static $instance = null;


    /**
     * @return self
     */
    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * Factory constructor
     */
    private function __construct()
    {

    }


    /**
     * @return ilCronJob[]
     */
    public function newInstances() : array
    {
        return [
            $this->newJobInstance()
        ];
    }


    /**
     * @param string $job_id
     *
     * @return ilCronJob|null
     */
    public function newInstanceById(string $job_id)/*: ?ilCronJob*/
    {
        switch ($job_id) {
            case Job::CRON_JOB_ID:
                return $this->newJobInstance();

            default:
                return null;
        }
    }


    public function newJobInstance() : Job
    {
        $job = new Job();

        return $job;
    }
}