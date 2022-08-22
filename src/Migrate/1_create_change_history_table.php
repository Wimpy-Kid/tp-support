<?php


class CreateChangeHistoryTable extends CherryLu\TpSupport\Migrate\MineMigrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $this->table('change_history')
             ->setComment('数据修改记录')
             ->setEngine('InnoDB')
             ->addColumn('model_type','string', ['comment' => '数据模型类名'])
             ->addColumn('model_id', 'integer', ['comment' => '数据ID'])
             ->addColumn('before', 'text', ['comment' => '修改前内容'])
             ->addColumn('after', 'text', ['comment' => '修改后内容'])
             ->addColumn('method', 'string', ['comment' => '请求方式'])
             ->addColumn('url', 'text', ['comment' => '请求url'])
             ->addColumn('param', 'text', ['comment' => '请求参数'])
             ->addFingerPrint()
             ->create();
    }
}
