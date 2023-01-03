<?php

namespace CAMOO\Di\Module;

use Cake\Core\App;
use Cake\Event\EventManager;
use InvalidArgumentException;
use Ray\Di\AbstractModule;

class DefaultModule extends AbstractModule
{
    protected ModuleCollection $configuration;

    public function __construct(ModuleCollection $configuration)
    {
        $this->configuration = $configuration;
        parent::__construct();
    }

    protected function configure()
    {
        $this->bind(EventManager::class);
        $this->install(new AssistedModule());
        array_map(function (mixed $module) {
            if (!is_string($module)) {
                return $module;
            }

            $class = App::classname($module, 'Di/Module');
            if (!$class) {
                throw new InvalidArgumentException('Invalid Di module name: ' . $module);
            }

            $this->install(new $class());
        }, $this->configuration->getIterator()->getArrayCopy());
    }
}
