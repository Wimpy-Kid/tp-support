<?php

namespace CherryLu\TpSupport;


class Auth {

    /** @var YourUserModel  */
    protected static $currentUserIns = null;

    /**
     * @return YourUserModel
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
