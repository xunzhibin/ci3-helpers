<?php

// 命名空间
namespace Xzb\Ci3\Helpers\Traits;

/**
 * 排序 解析
 */
trait ParseSort
{
    /**
     * 排序类型
     * arithmeticOperators(算术运算符) sortDirections(排序方向)
     *
     * @var string
     */
    protected $sortType = 'arithmeticOperators';

    /**
     * 排序分隔符
     *
     * @var string
     */
    protected $sortDelimiter = ',';

    /**
     * 算术 映射方向
     *
     * @var array
     */
    protected $arithmeticMappingDirections = [
        '+' => 'asc', // 升序
        '-' => 'desc', // 降序
    ];

    /**
     * 字段方向分隔符
     *
     * @var string
     */
    protected $fieldDirectiondelimiter = '|';

    /**
     * 按算术运算符排序
     *
     * @param string $sort
     * @return array
     */
    protected function orderByArithmeticOperators(string $sort): array
    {
		// 以 排序分隔符 分隔字符串, 循环处理每一个排序
		$order = array_map(function ($field) {
			// 循环  算术 映射方向
			foreach ($this->arithmeticMappingDirections as $mapKey => $direction) {
				// 比较两个字符串
				if (strncmp($field, $mapKey, strlen($mapKey)) === 0) {
					// 相等
					$column = current(array_filter(explode($mapKey, $field, 2)));

					return [
						$this->sortRemap()[$column] ?? $column => strtoupper($direction)
					];
				}
			}

			return false;
		}, explode($this->sortDelimiter, $sort));
	
		// 二维数组 合并成 一维数组
		$order = array_reduce(array_filter($order), 'array_merge', array());

		return $order;
    }

    /**
     * 按排序方向排序
     *
     * @param string $sort
     * @return array
     */
    protected function orderBySortDirections(string $sort): array
    {
		// 以 排序分隔符 分隔字符串, 循环处理每一个排序
		$order = array_map(function ($field) {
			// 检测 字段方向分隔符 是否存在
			if (mb_strpos($field, $this->fieldDirectiondelimiter) === false) {
				return FALSE;
			}

			// 分隔 排序字段、方向
			list($column, $direction) = explode($this->fieldDirectiondelimiter, $field);

			// 检测 排序方向
			if (! in_array($direction, $this->arithmeticMappingDirections)) {
				return FALSE;
			}

			return [
				$this->sortRemap()[$column] ?? $column => $direction
			];
		}, explode($this->sortDelimiter, $sort));

		// 二维数组 合并成 一维数组
		$order = array_reduce(array_filter($order), 'array_merge', array());

		return $order;
    }

	/**
	 * 排序 字段 映射
	 * 
	 * @return array
	 */
	public function sortRemap(): array
	{
		return [
			// 提交key => DB列
		];
	}

    /**
     * 解析 排序
     *
     * @param string $sort
     * @return array
     */
    public function parseSort(string $sort): array
    {
		if (! $sort) {
			return [];
		}

        switch ($this->sortType) {
            // 排序方向
            case 'sortDirections':
                $orderBy = $this->orderBySortDirections($sort);
                break;
            // 算术运算符
            case 'arithmeticOperators':
            default :
                $orderBy = $this->orderByArithmeticOperators($sort);
                break;
        }

		return $orderBy;
    }

}
