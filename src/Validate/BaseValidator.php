<?php

namespace CherryLu\TpSupport\Validate;

use think\Validate;

abstract class BaseValidator extends Validate {

    public final function __construct() {
        parent::__construct();
        $this->beforeValidate();
    }

    protected function parseErrorMsg(string $msg, $rule, string $title) {
        $title = $this->formMaps[ $title ] ?? $title;
        return parent::parseErrorMsg($msg,$rule,$title);
    }

    abstract public function beforeValidate();

}
