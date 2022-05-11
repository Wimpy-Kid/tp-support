<?php


namespace cherrylu\TpSupport\Model;

use cherrylu\TpSupport\Auth;
use Carbon\Carbon;
use think\Model;

trait AutoFingerPrint {

    protected static $snapShot = true;

    public function snapShootOn() {
        static::$snapShot = true;
        return $this;
    }

    public function snapShootOff() {
        static::$snapShot = false;
        return $this;
    }

    public static function onBeforeInsert(Model $model) {
        self::setCreateInfo($model);
    }

    public static function onBeforeUpdate(Model $model) {
        self::setUpdateInfo($model);
        static::$snapShot && self::createLog($model);
    }

    public static function onBeforeDelete(Model $model) {
        self::setDeleteInfo($model);
        static::$snapShot && self::createLog($model);
    }

    public static function onAfterInsert(Model $model) {
        static::$snapShot && self::createLog($model, true);
    }

    protected static function setCreateInfo(Model $model) {
        if ( !static::$saveQuietly ) {
            $model->setAttr('create_time', Carbon::now()->toDateTimeString());
            $model->setAttr('create_by', Auth::user()->getAttr('id'));
            $model->setAttr('update_time', Carbon::now()->toDateTimeString());
            $model->setAttr('update_by', Auth::user()->getAttr('id'));
        }
    }

    protected static function setUpdateInfo(Model $model) {
        if ( !static::$saveQuietly ) {
            $model->setAttr('update_time', Carbon::now()->toDateTimeString());
            $model->setAttr('update_by', Auth::user()->getAttr('id'));
        }
    }

    protected static function setDeleteInfo(Model $model) {
        if ( !static::$saveQuietly ) {
            $model->setAttr('delete_time', Carbon::now()->toDateTimeString());
            $model->setAttr('delete_by', Auth::user()->getAttr('id'));
        }
    }

    protected static function createLog(Model $model, $isCreate = false) {
        if ( !static::$saveQuietly ) {
            $request = \request();
            ChangeHistory::create([
                'model_type'  => get_class($model),
                'model_id'    => $model->getKey(),
                'before'      => $isCreate ? [] : $model->getOrigin(),
                'after'       => $model->toArray(),
                'method'      => $request->method(),
                'url'         => $request->url(),
                'param'       => $request->all(),
                'create_time' => Carbon::now()->toDateTimeString(),
                'create_by'   => Auth::user()->getkey(),
                'update_time' => Carbon::now(),
                'update_by'   => Auth::user()->getkey(),
            ]);
        }
    }

    public function changeHistories() {
        return $this->hasMany(ChangeHistory::class, 'model_id')->where('model_type', static::class);
    }

}
