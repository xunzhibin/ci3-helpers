<?php

// 命名空间
namespace Xzb\Ci3\Helpers\Traits;

/**
 * 检索 解析
 */
trait ParseSearch
{
	/**
	 * 检索 字段 映射
	 * 
	 * @return array
	 */
	public function searchRemap(): array
	{
		return [
			// 提交key => DB列
		];
	}

	/**
	 * 解析 检索
	 * 
	 * @param string $searches
	 * @return array
	 */
	public function parseSearch(string $searches): array
	{
		if (! $searches) {
			return [];
		}

		$searchRemap = $this->searchRemap();

		return array_map(function ($search) use ($searchRemap) {
			return array_key_exists($search, $searchRemap)
						? $searchRemap[$search]
						: $search;
		}, explode(',', $searches));
	}

}
