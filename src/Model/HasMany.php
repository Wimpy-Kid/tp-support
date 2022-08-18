<?php

namespace CherryLu\TpSupport\Model;

use Closure;
use think\Model;

/**
 * 一对多关联类
 */
class HasMany extends \think\model\relation\HasMany
{
    /**
     * 一对多 关联模型预查询
     *
     * @param array $where
     * @param array $subRelation
     * @param Closure|null $closure
     * @param array $cache
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function eagerlyOneToMany(array $where, array $subRelation = [], Closure $closure = null, array $cache = []): array
    {
        $foreignKey = $this->foreignKey;

        $this->query->removeWhereField($this->foreignKey);

        // 预载入关联查询 支持嵌套预载入
        if ($closure) {
            $this->baseQuery = true;
            $closure($this->getClosureType($closure));
        }

        if ($this->withoutField) {
            $this->query->withoutField($this->withoutField);
        }

        $list = $this->query
            ->where($where)
            ->cache($cache[0] ?? false, $cache[1] ?? null, $cache[2] ?? null)
            ->with($subRelation)
            ->select();

        // 组装模型数据
        $data = [];

        foreach ($list as $set) {
            $key = $set->$foreignKey;

            if ($this->withLimit && isset($data[$key]) && count($data[$key]) >= $this->withLimit) {
                continue;
            }

            $data[$key][] = $set;
        }

        return $data;
    }

    public function eagerlyResult(Model $result, string $relation, array $subRelation = [], Closure $closure = null, array $cache = []): void
    {
        $localKey = $this->localKey;

        if (isset($result->$localKey)) {
            $pk   = $result->$localKey;
            $data = $this->eagerlyOneToMany([
                [$this->foreignKey, '=', $pk],
            ], $subRelation, $closure, $cache);

            // 关联数据封装
            if (!isset($data[$pk])) {
                $data[$pk] = [];
            }

            $relateData = [];
            $pk = $result->$localKey;

            if ( false === strpos($pk, ',') ) {
                if (isset($data[$pk])) {
                    $relateData = $data[$pk];
                }
            } else {
                $pk = explode(',', $pk);
                foreach ( $pk as $item ) {
                    if ( isset($data[$item][0]) ) {
                        $relateData[] = $data[$item][0];
                    }
                }
            }

            $result->setRelation($relation, $this->resultSetBuild($relateData, clone $this->parent));

        }
    }

    public function eagerlyResultSet(array &$resultSet, string $relation, array $subRelation, Closure $closure = null, array $cache = []): void
    {
        $localKey = $this->localKey;
        $range    = [];

        foreach ($resultSet as $result) {
            // 获取关联外键列表
            if (isset($result->$localKey)) {
                if ( false === strpos($result->$localKey, ',') ) {
                    $range[] = $result->$localKey;
                } else {
                    $range = array_merge($range, explode(',', $result->$localKey));
                }
            }
        }

        if (!empty($range)) {
            $data = $this->eagerlyOneToMany([
                [$this->foreignKey, 'in', $range],
            ], $subRelation, $closure, $cache);

            // 关联数据封装
            foreach ($resultSet as $result) {
                $relateData = [];
                $pk = $result->$localKey;

                if ( false === strpos($pk, ',') ) {
                    if (isset($data[$pk])) {
                        $relateData = $data[$pk];
                    }
                } else {
                    $pk = explode(',', $pk);
                    foreach ( $pk as $item ) {
                        if ( isset($data[$item][0]) ) {
                            $relateData[] = $data[$item][0];
                        }
                    }
                }

                $result->setRelation($relation, $this->resultSetBuild($relateData, clone $this->parent));
            }
        }
    }
}
