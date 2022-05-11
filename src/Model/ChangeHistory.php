<?php


namespace cherrylu\TpSupport\Model;

use think\model\concern\SoftDelete;

class ChangeHistory extends BaseModel {

    use SoftDelete;

    public $modelName = '历史记录';

    protected $type = [
        'before' => 'array',
        'after' => 'array',
        'param' => 'array',
    ];

}
