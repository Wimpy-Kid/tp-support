<?php


namespace CherryLu\TpSupport\Model;

use think\db\BaseQuery as Query;
use think\Model;
use think\Collection;
use think\model\Relation;

class BaseModel extends Model
{
    use CherryRelationShip;

    public $modelName;

    private $data;

    protected $autoWriteTimestamp = true;

    protected $relationWithOutGlobalScope = null;

    protected $deleteTime = 'delete_time';
    protected $updateTime = 'update_time';
    protected $createTime = 'create_time';

    protected $hidden = [ 'delete_time', 'delete_by', 'update_by', 'update_time' ];

    protected static $saveQuietly = false;

    public static $withoutGlobalScope = [];
    /**
     * @var bool|mixed
     */
    private $forceExists;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $lastTrace = $traces[0];
        $relationSetting = $traces[1];
        if (strpos($lastTrace['file'] ?? '', 'RelationShip') !== false) {
            if (isset($relationSetting['object']->relationWithOutGlobalScope)) {
                if ($scope = $relationSetting['object']->relationWithOutGlobalScope) {
                    $this::$withoutGlobalScope = $scope;
                } else {
                    $this::$withoutGlobalScope = $this->globalScope;
                }
            }
        } elseif ( strpos($lastTrace['file'] ?? '', 'HasMany') !== false ) {
            $parent = $relationSetting['object']->getParent();
            $model = $lastTrace['object'];
            if ( isset($parent->relationWithOutGlobalScope) ) {
                if ($scope = $parent->relationWithOutGlobalScope) {
                    $model::$withoutGlobalScope = $scope;
                } else {
                    $model::$withoutGlobalScope = $model->globalScope;
                }
            }
        }
    }

    protected function forceExists () {
        $this->exists();
        $this->forceExists = true;
    }

    /**
     * 强制更新被软删除的数据
     *
     * @param array $data
     * @param string|null $sequence
     *
     * @return bool
     */
    public function forceSave(array $data = [], string $sequence = null):bool {
        $this->forceExists();
        return parent::save($data, $sequence);
    }

    /**
     * 保存写入数据
     * @access protected
     * @return bool
     */
    protected function updateData(): bool
    {
        // 事件回调
        if (false === $this->trigger('BeforeUpdate')) {
            return false;
        }

        $this->checkData();

        // 获取有更新的数据
        $data = $this->getChangedData();
        $this->data = $this->getData();

        if (empty($data)) {
            // 关联更新
            if (!empty($this->relationWrite)) {
                $this->autoRelationUpdate();
            }

            return true;
        }

        if ($this->autoWriteTimestamp && $this->updateTime) {
            // 自动写入更新时间
            $data[$this->updateTime]       = $this->autoWriteTimestamp();
            $this->data[$this->updateTime] = $data[$this->updateTime];
        }

        // 检查允许字段
        $allowFields = $this->checkAllowFields();

        foreach ($this->relationWrite as $name => $val) {
            if (!is_array($val)) {
                continue;
            }

            foreach ($val as $key) {
                if (isset($data[$key])) {
                    unset($data[$key]);
                }
            }
        }

        // 模型更新
        $db = $this->db();

        $db->transaction(function () use ($data, $allowFields, $db) {
            $this->key = null;
            $where     = $this->getWhere();
            if ( $this->forceExists ) {
                $db = $db->withTrashed();
            }
            $result = $db->where($where)
                         ->strict(false)
                         ->cache(true)
                         ->setOption('key', $this->key)
                         ->field($allowFields)
                         ->update($data);

            $this->checkResult($result);

            // 关联更新
            if (!empty($this->relationWrite)) {
                $this->autoRelationUpdate();
            }
        });

        // 更新回调
        $this->trigger('AfterUpdate');

        return true;
    }

    /**
     * @param array|string $with
     *
     * @return BaseModel|Query
     */
    public static function with($with) {
        $static = static::query();
        if ( is_string($with) ) {
            if ( false !== strpos($with, ':') ) {
                $with = explode(':', str_replace(' ', '', $with));
                $relation = $with[0];
                $columns = explode(',', $with[1]);
                $with = [ $relation => function(Relation $relation) use ( $columns ) { $relation->field($columns); } ];
            }
        } elseif ( is_array($with) ) {
            foreach ( $with as $r => $f ) {
                if ( is_string($f) ) {
                    if ( false !== strpos($f, ':') ) {
                        $f = explode(':', str_replace(' ', '', $f));
                        $relation = $f[0];
                        $columns = explode(',', $f[1]);
                        unset($with[$r]);
                        $with[$relation] = function(Relation $relation) use ( $columns ) { $relation->field($columns); };
                    }
                }
            }
        }
        return $static->with($with);
    }

    /**
     * @param array $data
     *
     * @return Model
     */
    public function saveQuietly($data = []) {
        static::$saveQuietly = true;
        $this->save($data);
        static::$saveQuietly = false;
        return $this;
    }

    public function load($relations) {
        if ( is_string($relations) ) {
            $this->setRelation($relations,$this->$relations()->getRelation());
            return $this;
        } if ( is_array($relations) ) {
            foreach ( $relations as $relation => $f ) {
                if ( is_string($f) ) {
                    $this->setRelation($f,$this->$f()->getRelation());
                } elseif ( $f instanceof \Closure ) {
                    $this->setRelation($relation,$this->$relation()->where($f)->getRelation());
                }
            }
        }
        return $this;
    }

    public function loadMissing($relations) {
        if ( is_string($relations) ) {
            $this->setRelation($relations,$this->$relations);
            return $this;
        } if ( is_array($relations) ) {
            foreach ( $relations as $relation => $f ) {
                if ( is_string($f) ) {
                    $this->setRelation($f,$this->$f);
                } elseif ( $f instanceof \Closure ) {
                    $this->setRelation($relation,$this->$relation()->where($f)->getRelation());
                }
            }
        }
        return $this;
    }

    /**
     * @param null $data
     *
     * @return BaseModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function find($data = null) {
        return parent::find($data);
    }

    /**
     * @param array $withOutScope
     *
     * @return Query
     */
    public static function query($withOutScope = [])
    {
        $static = (new static);
        return $static->db($withOutScope)->append($static->rawOptions()['append'] ?? $static->getAppend() ?? []);
    }

    public function rawOptions()
    {
        return $this->options;
    }

    public function getAppend()
    {
        return $this->append;
    }

    public function db($withoutScope = []): Query
    {
        return parent::db($withoutScope ?: (static::$withoutGlobalScope ?? []));
    }

    public static function updateOrCreate($where, $update)
    {
        $model = (new static)->db()->where($where)->find();
        if (!$model) {
            $model = (new static($where));
        }
        $model->save($update);
        return $model;
    }

    public static function findOrCreate($where, $data = [])
    {
        $model = (new static)->db()->where($where)->find();
        if (!$model) {
            $model = (new static($where));
            $data && $model->save($data);
        }
        return $model;
    }

    public static function saveGetIns(array $data = []): Model
    {
        $model = (new static);
        $model->save($data);
        return $model;
    }

    public static function saveAllGetIns(array $data = []): Collection
    {
        $model = (new static);
        return $model->saveAll($data);
    }

    protected function relationWithOutGlobalScope($scope = [])
    {
        $this->relationWithOutGlobalScope = $scope;
        return $this;
    }

    /**
     * 数据读取 类型转换
     * @access protected
     * @param  mixed        $value 值
     * @param  string|array $type  要转换的类型
     * @return mixed
     */
    protected function readTransform($value, $type)
    {
        if (is_null($value)) {
            return;
        }

        if (is_array($type)) {
            [$type, $param] = $type;
        } elseif (strpos($type, ':')) {
            [$type, $param] = explode(':', $type, 2);
        }

        switch ($type) {
            case 'string':
                $value = (string) $value;
                break;
            case 'numeric':
                $value = is_numeric($value) ? $value * 1 : 0;
                break;
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
                if (empty($param)) {
                    $value = (float) $value;
                } else {
                    $value = (float) number_format($value, (int) $param, '.', '');
                }
                break;
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'timestamp':
                if (!is_null($value)) {
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value  = $this->formatDateTime($format, $value, true);
                }
                break;
            case 'datetime':
                if (!is_null($value)) {
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value  = $this->formatDateTime($format, $value);
                }
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
            case 'array':
                $value = empty($value) ? [] : json_decode($value, true);
                break;
            case 'object':
                $value = empty($value) ? new \stdClass() : json_decode($value);
                break;
            case 'serialize':
                try {
                    $value = unserialize($value);
                } catch (\Exception $e) {
                    $value = null;
                }
                break;
            default:
                if (false !== strpos($type, '\\')) {
                    // 对象类型
                    $value = new $type($value);
                }
        }

        return $value;
    }
}
