<?php

namespace CherryLu\TpSupport;


class Auth {

    /** @var \app\model\Account | \app\model\User  */
    protected static $currentUserIns = null;

    /**
     * @return \app\model\Account | \app\model\User
     */
    public static function user() {
        return Auth::$currentUserIns;
    }

    public static function setCurrentUser($user) {
        return Auth::$currentUserIns = $user;
    }

    public static function id() {
        return Auth::$currentUserIns ? Auth::$currentUserIns->getKey() : null;
    }

}
