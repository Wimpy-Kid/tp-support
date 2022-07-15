<?php


namespace Cherrylu\TpSupport\Migrate;


use think\migration\Migrator;

class MineMigrator extends Migrator {

    /**
     * @param string $tableName
     * @param array  $options
     * @return MinePhinxTable
     */
    public function table($tableName, $options = [])
    {
        return new MinePhinxTable($tableName, $options, $this->getAdapter());
    }



}
