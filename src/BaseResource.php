<?php

namespace cherrylu\TpSupport;

use think\helper\Arr;
use think\model\Collection;
use think\Paginator;

abstract class BaseResource {

    protected $rawData;

    /**
     * BaseResource constructor.
     *
     * @param mixed $rawData
     */
    public function __construct($rawData) {
        $this->rawData = $rawData;
    }

    /**
     * @param \think\Collection | Paginator $rawData
     *
     * @return array
     */
    final public static function collection($rawData) {
        $rawData = [$rawData];
        $formatter = self::generateFormatter($rawData);
        $buff = [];
        foreach ( $rawData[0] as $rawDatum ) {
            $formatter->rawData = $rawDatum;
            $buff[]             = $formatter->toArray();
        }
        return $buff;
    }

    /**
     * @param ...$rawData
     *
     * @return array
     */
    final public static function format(...$rawData) {
        $formatter = self::generateFormatter($rawData);
        $rawData   = Arr::first($rawData);
        if ( $rawData instanceof Collection ) {
            $buff = [];
            foreach ( $rawData as $rawDatum ) {
                $formatter->rawData = $rawDatum;
                $buff[]             = $formatter->toArray();
            }
            return $buff;
        } else {
            $formatter->rawData = $rawData;
            return $formatter->toArray();
        }
    }

    private static function generateFormatter( &$rawData ) {
        $reflect     = new \ReflectionClass(static::class);
        $constructor = $reflect->getMethod('__construct');
        foreach ( $constructor->getParameters() as $num => $parameter ) {
            isset($rawData[ $num ]) || $rawData[] = null;
        }
        return app(static::class, $rawData);
    }

    abstract function toArray();

    public function __get($name) {
        return $this->rawData->$name;
    }
}
