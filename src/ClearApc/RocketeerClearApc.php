<?php

namespace ClearApc\Rocketeer;


use Illuminate\Container\Container;
use Rocketeer\Abstracts\AbstractPlugin;
use Rocketeer\Services\TasksHandler;

class RocketeerClearApc extends AbstractPlugin
{

    /**
     * Setup the plugin.
     *
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        parent::__construct($app);
        $this->configurationFolder = __DIR__ . '/../config';
    }


    /**
     * @inheritdoc
     */
    public function register(Container $app)
    {
    }

    /**
     * @inheritdoc
     */
    public function onQueue(TasksHandler $queue)
    {
    }
}