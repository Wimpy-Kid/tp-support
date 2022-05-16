<?php

declare (strict_types = 1);

namespace CherryLu\TpSupport\Model;

use think\helper\Str;

/**
 * 模型关联处理
 */
trait CherryRelationShip
{

    /**
     * BELONGS TO MANY 关联定义
     * @access public
     * @param  string $model      模型名
     * @param  string $middle     中间表/模型名
     * @param  string $foreignKey 关联外键
     * @param  string $localKey   当前模型关联键
     * @return BelongsToMany
     */
    public function cherryBelongsToMany(string $model, string $middle = '', string $foreignKey = '', string $localKey = '', $withTrashed = false): BelongsToMany
    {
        // 记录当前关联信息
        $model      = $this->parseModel($model);
        $name       = Str::snake(class_basename($model));
        $middle     = $middle ?: Str::snake($this->name) . '_' . $name;
        $foreignKey = $foreignKey ?: $name . '_id';
        $localKey   = $localKey ?: $this->getForeignKey($this->name);

        return new BelongsToMany($this, $model, $middle, $foreignKey, $localKey, $withTrashed);
    }

}
