<?php
declare(strict_types=1);

namespace CAMOO\Validation;

/**
 * Trait ValidatorLocatorTrait
 * @author CamooSarl
 */
trait ValidatorLocatorTrait
{
    /** @var array $__adapterFactory */
    private $__adapterFactory = [AdapterFactory::class, 'create'];

    /**
     * gets adapter factory
     *
     * @return AdapterFactory
     */
    public function getValidatorLocator()
    {
        return call_user_func($this->__adapterFactory);
    }
}
