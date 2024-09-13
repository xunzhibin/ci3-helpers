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
	 * 数据类型 缓存
	 * 
	 * @var array
	 */
	protected static $typeCache = [];

	/**
	 * 是否为 自定义日期时间 类型
	 * 
	 * @param string $type
	 * @return bool
	 */
	public static function isCustomDateTimeType(string $type): bool
	{
		return strncmp($type, $str = 'datetime:', strlen($str)) === 0;
	}

	/**
	 * 是否为 小数 类型
	 * 
	 * @param string $type
	 * @return bool
	 */
	public static function isDecimalType(string $type): bool
	{
		return strncmp($type, $str = 'decimal:', strlen($str)) === 0;
	}

	/**
	 * 解析 数据类型
	 * 
	 * @param string $type
	 * @return string
	 */
	public static function resolveType(string $type): string
	{
		// 缓存中 存在
		if (isset(static::$typeCache[$type])) {
			return static::$typeCache[$type];
		}

		// 是否为 自定义日期时间 类型
		if (static::isCustomDateTimeType($type)) {
			$convertedType = 'custom_datetime';
		}
		// 是否为 小数 类型
		elseif (static::isDecimalType($type)) {
			$convertedType = 'decimal';
		}
		else {
			$convertedType = trim(strtolower($type));
		}

		return static::$typeCache[$type] = $convertedType;
	}

	/**
	 * 转换 值类型
	 * 
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public static function valueType(string $type, $value)
	{
		//  null时, 不转换
		if (is_null($value)) {
			return $value;
		}

		// 检测 类型
		switch (static::resolveType($type)) {
			// 布尔类型
			case 'bool':
			case 'boolean':
				return (bool)$value;
			// 整形
			case 'int':
			case 'integer':
				return (int)$value;
			// 浮点型
			case 'real': // 实数 
			case 'float': // 浮点数
			case 'double': // 双精度数 
				return static::toFloat($value);
			// 小数
			case 'decimal':
				$decimals = explode(':', $type, 2)[1];
				return number_format(static::toFloat($value), $decimals, '.', '');
			// 字符串
			case 'string':
				return (string)$value;
			// 数组
			case 'array':
				return static::toArray($value);
			// 对象
			case 'object':
				return static::toObject($value);
			// JSON 字符串
			case 'json':
				return static::toJson($value);
			// Unix时间戳
			case 'timestamp':
				return static::toCustomDateFormat($value, $format = 'U');
			// 日期
			case 'date':
				return static::toCustomDateFormat($value, $format = 'Y-m-d');
			// 日期时间
			case 'datetime':
				return static::toCustomDateFormat($value, $format = 'Y-m-d H:i:s');
			// 自定义 日期时间
			case 'custom_datetime':
				$format = explode(':', $type, 2)[1];
				return static::toCustomDateFormat($value, $format);
		}

		return $value;
	}

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
        // 进行 JSON 编码
		$json = json_encode($value);

        // JSON 编码时发生错误
		if (json_last_error() !== JSON_ERROR_NONE) {
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
            $array = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
				if (is_array($array)) {
					return $array;
				}

				$value = $array;
            }

            if (! strlen($value)) {
                return [];
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
			// 根据 格式 创建 Carbon实例
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
