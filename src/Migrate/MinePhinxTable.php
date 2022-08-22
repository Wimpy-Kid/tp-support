<?php


namespace CherryLu\TpSupport\Migrate;


use think\migration\db\Table;

class MinePhinxTable extends Table {

    /**
     * @return Table
     */
    public function addFingerPrint(): Table {
        $this->addColumn('create_time', 'timestamp', ['comment' => '数据创建时间', 'default' => 'CURRENT_TIMESTAMP'])
             ->addColumn('create_by', 'integer', ['comment' => '创建人', 'signed' => false])

             ->addColumn('update_time', 'timestamp', ['comment' => '数据更新时间', 'default' => 'CURRENT_TIMESTAMP'])
             ->addColumn('update_by', 'integer', ['comment' => '更新人', 'signed' => false])

             ->addColumn('delete_time', 'timestamp', ['comment' => '数据删除时间', 'null' => true])
             ->addColumn('delete_by', 'integer', ['comment' => '删除人', 'signed' => false, 'null' => true]);

        $this->addIndex('delete_time');

        return $this;
    }

    /**
     * @param \Phinx\Db\Table\Column|string $columnName
     * @param null $type
     * @param array $options
     *
     * @return $this
     */
    public function addColumn($columnName, $type = null, $options = [])
    {
        parent::addColumn($columnName, $type, $options);
        return $this;
    }

    /**
     * @param string $columnName
     * @param null $newColumnType
     * @param array $options
     *
     * @return $this
     */
    public function changeColumn($columnName, $newColumnType = null, $options = []) {
        parent::changeColumn($columnName, $newColumnType, $options);
        return $this;
    }

}
