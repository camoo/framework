<?php

namespace CAMOO\Di\Interceptor;

use CAMOO\Di\CamooDi;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use ReflectionException;

class AssistedInterceptor implements MethodInterceptor
{
    /**
     * Intercepts any method and injects instances of the missing arguments
     * when they are type hinted
     *
     * @throws ReflectionException
     */
    public function invoke(MethodInvocation $invocation)
    {
        $invocation->getThis();
        $parameters = $invocation->getMethod()->getParameters();
        $arguments = $invocation->getArguments()->getArrayCopy();
        $assisted = [];

        foreach ($parameters as $k => $p) {
            $hint = $p->getType();
            if ($hint !== null) {
                $assisted[$k] = CamooDi::get($hint->getName());
                continue;
            }
            if (isset($arguments[$k])) {
                $assisted[$k] = array_shift($arguments);
                continue;
            }
            $assisted[$k] = $p->getDefaultValue();
        }

        $invocation->getArguments()->exchangeArray($assisted);

        return $invocation->proceed();
    }
}
