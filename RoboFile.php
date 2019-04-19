<?php

// @codingStandardsIgnoreStart
use Robo\Exception\TaskException;

/**
 * Base tasks for setting up a module to test within a full Drupal environment.
 *
 * This file expects to be called from the root of a Drupal site.
 *
 * @class RoboFile
 * @codeCoverageIgnore
 */
class RoboFile extends \Robo\Tasks
{

    /**
     * The database URL.
     */
    const DB_URL = 'sqlite://tmp/site.sqlite';

    /**
     * The website's URL.
     */
    const DRUPAL_URL = 'http://drupal.ddev.local';

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        // Treat this command like bash -e and exit as soon as there's a failure.
        $this->stopOnFail();
    }

    /**
     * Command to run unit tests.
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobRunUnitTests()
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->runUnitTests());
        return $collection->run();
    }

    /**
     * Command to check coding standards.
     *
     * @return null|\Robo\Result
     *   The result of the set of tasks.
     *
     * @throws \Robo\Exception\TaskException
     */
    public function jobCheckCodingStandards()
    {
        return $this->taskExecStack()
            ->stopOnFail()
            ->exec('vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer')
            ->exec('vendor/bin/phpcs --standard=Drupal web/modules/custom')
            ->exec('vendor/bin/phpcs --standard=DrupalPractice web/modules/custom')
            ->run();
    }

    /**
     * Command to check config import.
     *
     * @return \Robo\Result
     *   The resul tof the collection of tasks.
     */
    public function jobCheckConfigImport()
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->runUpdatePath());
        return $collection->run();
    }

    /**
     * Updates the database.
     *
     * We can't use the drush() method because this is running within a docker-compose
     * environment.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runUpdatePath()
    {
        $tasks = [];
        $tasks[] = $this->taskExec('ddev exec drush --yes updatedb');
        $tasks[] = $this->taskExec('ddev exec drush --yes config-import');
        return $tasks;
    }

    /**
     * Run unit tests.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runUnitTests()
    {
        $force = true;
        $tasks = [];
        $tasks[] = $this->taskExecStack()
            ->dir('web')
            ->exec('export SIMPLETEST_DB="' . static::DB_URL . '"')
            ->exec('../vendor/bin/phpunit -c core --debug --coverage-clover ../build/logs/clover.xml --verbose modules/custom');
        return $tasks;
    }

    /**
     * Return drush with default arguments.
     *
     * @return \Robo\Task\Base\Exec
     *   A drush exec command.
     */
    protected function drush()
    {
        // Drush needs an absolute path to the docroot.
        $docroot = $this->getDocroot() . '/web';
        return $this->taskExec('vendor/bin/drush')
            ->option('root', $docroot, '=');
    }

    /**
     * Get the absolute path to the docroot.
     *
     * @return string
     *   The document root.
     */
    protected function getDocroot()
    {
        return (getcwd());
    }

}
