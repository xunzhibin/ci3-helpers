<?php

// 命名空间
namespace Xzb\Ci3\Helpers;

// PHP 异常类
use RuntimeException;

// PHP 日期时间接口
use DateTimeInterface;

// 第三方 日期时间类
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * 转换
 */
class Transform
{
    /**
     * 转换为 浮点类型
     * 
     * @param mixed $value
     * @return mixed
     */
    public static function toFloat($value)
    {
		switch ($value) {
			case 'Infinity':
				return INF;
			case '-Infinity':
				return -INF;
			case 'NaN':
				return NAN;
			default:
				return (float)$value;
		}
    }

    /**
     * 转换为 JSON 字符串
     * 
     * @param mixed $value
     * @return string
     */
    public static function toJson($value): string
    {
		$json = json_encode($value);
        if ($json === false) {
			throw new RuntimeException(
				"Unable to encode to JSON: " . json_last_error_msg()
			);
        }

        return $json;
    }

    /**
     * 转换为 对象
     * 
     * @param mixed $value
     * @return \stdClass
     */
    public static function toObject($value): object
    {
        if (is_object($value)) {
            $value = static::toJson($value);
        }

        if (is_string($value)) {
            $object = json_decode($value);
            // 没有错误发生
            if (json_last_error() === JSON_ERROR_NONE && is_object($object)) {
                return $object;
            }
        }

        return (object)$value;
    }

    /**
     * 转换为 数组
     * 
     * @param mixed $value
     * @return array
     */
    public static function toArray($value): array
    {
        if (is_object($value)) {
            $value = static::toJson($value);
        }

        if (is_string($value)) {
            if (! strlen($value)) {
                return [];
            }

            $array = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($array)) {
                return $array;
            }
        }

        return (array)$value;
    }

    /**
     * 转换为 日期时间 对象
     * 
     * @param mixed $value
     * @param string $format
     * @return \Xzb\Ci3\Helpers\Date
     */
    public static function toDateObject($value, string $format = '')
    {
		// 第三方库 Carbon实例
		if ($value instanceof CarbonInterface) {
			// 返回 Carbon实例
			return Date::instance($value);
		}

		// PHP库 DateTime实例
		if ($value instanceof DateTimeInterface) {
			// 按 日期格式、时区 解析 返回 Carbon实例
			return Date::parse(
				$value->format('Y-m-d H:i:s.u'), $value->getTimezone()
			);
		}

		// 数字或数字字符串
		if (is_numeric($value)) {
			// 以 UNIX时间戳 创建 Carbon实例
			return Date::createFromTimestamp($value);
		}

		// 标准 日期 格式
		if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value)) {
			// 返回 Carbon实例
			return Date::instance(
				// 根据 标准日期格式 解析 返回 日期时间格式 例如：2012-01-31 00:00:00
				Carbon::createFromFormat('Y-m-d', $value)->startOfDay()
			);
		}

		try {
			// 根据 存储格式 创建 Carbon实例
			$date = Date::createFromFormat($format, $value);
		} catch (\InvalidArgumentException $e) {
			// 无效参数异常
			$date = false;
		}

		return $date ?: Date::parse($value);
    }

    /**
     * 转换为 自定义日期格式
     * 
     * @param mixed $value
     * @param string $format
     * @return mixed
     */
    public static function toCustomDateFormat($value, string $format)
    {
		if (empty($value)) {
			return $value;
		}

		return static::toDateObject($value)->format($format);
    }
}