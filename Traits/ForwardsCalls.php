<?php

// 命名空间
namespace Xzb\Ci3\Helpers\Traits;

// PHP 异常类
use BadMethodCallException;
use Error;

/**
 * 调用转发
 */
trait ForwardsCalls
{
    /**
     * 将 方法调用 转发到 给定对象
     * 
     * @param object $object
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    protected function forwardCallTo($object, $method, $parameters)
    {
        try {
            return $object->{$method}(...$parameters);
        }
        catch (Error|BadMethodCallException $e) {
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';

            if (! preg_match($pattern, $e->getMessage(), $matches)) {
                throw $e;
            }

            if ($matches['class'] != get_class($object) || $matches['method'] != $method) {
                throw $e;
            }

			throw new BadMethodCallException(sprintf(
				'Call to undefined method %s::%s()', static::class, $method
			));
        }
        catch (Throwable $e) {
            throw $e;
        }
    }

}
