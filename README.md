让 ThinkPHP 好用一点，对IDE更友好，也更贴近 Laravel 框架的习惯；
虽然也还是很烂，尽力了🤣。本包仅迎合作者习惯，也有很多问题，不建议大伙用。

目录
-  [安装](#install)
-  [表单验证](#validate)
-  [全局身份信息 Auth](#auth)
-  [表格导出](#export)
-  [request](#request)
-  [模型 Model](#model)
-  [数据迁移 migration](#migration)
-  [数据修改记录 AutoFingerPrint](#change-history)

<h3 id="install">安装</h3>

安装命令

```
composer require cherrylu/tp-support
```

<span id="tips">tips</span>: 安装完成后如果你想用到本包的<a href="#change-history">`AutoFingerPrint`</a>模块，请将包内的`1_create_change_history_table`文件复制到项目的`migrations`文件夹，并在项目根目录运行`php think migrate:run`命令。


<h3 id="validate">表单验证</h3>

表单验证支持TP原生的所有语法

```php
    namespace your\namespace;

    use CherryLu\TpSupport\Validate\BaseValidator;

    class UpdateOrCreateActivity extends BaseValidator {

        /** 支持TP的常规写法 */
        protected $rule = [
            'cover_img' => 'require|url',
            'content'   => 'require',
            'order'     => 'require|number',
            'available' => 'require',
        ];
    
        /** 支持TP的常规写法 */
        protected $message = [
            'cover_img.require' => '封面不能为空',
        ];
    
        /** 
        * 也可以只定义各个表单的名称，会自动返回对应的验证信息
        * 用于取代 $message 
        */
        protected $formMaps = [
            'id'           => '关键信息',
            'cover_img'    => '封面',
            'content'      => '详细内容',
            'order'        => '优先级',
            'available'    => '状态',
        ];
        
        /** 
        * 进行验证前的预处理函数 
        * 在这里你可以进一步构建验证规则
        * 或者进行其他业务
        */
        public function beforeValidate() {
            $this->rule = [
                'cover_img' => 'require|url',
                'content'   => 'require',
                'order'     => 'require|number',
                'available' => 'require',
            ];
    
            if ( $this->request->method() == 'PUT' ) {
                $this->rule['id'] = 'require';
            }
        }
    }
```

<h3 id="auth">全局身份信息 Auth</h3>

系统进行完身份验证后，可将用户的模型数据存入 
> Auth::setCurrentUser(<span id="current_model">User::find($userId)</span>)

之后便可以在其他地方使用了

`Auth::user()` 获取当前登录用户model，它即是你<a href="#current_model">传入的模型</a>，具有该模型的所有特性；

`Auth::id()` 获取当前用户的主键

<h3 id="export">表格导出</h3>

```php

$data = [
    [
        'nick_name'    => '张三',
        'phone'        => '15555555555',
        'goods'        => [ // 合并单元格
            [
                'goods_name'  => '饼干1',
                'create_time' => '2020-02-02 10:10:10',
            ],[
                'goods_name'  => '饼干2',
                'create_time' => '2020-02-03 10:10:10',
            ]
        ],
        'first_visit'  => '2020-02-02 10:10:10',
        'latest_visit' => '2020-02-02 10:10:10',
        'is_forbidden' => '是',
        'other_data'   => 'anything else', 
    ], // ...
];

\CherryLu\TpSupport\Exporter::export([
    'nick_name'    => '用户昵称',
    'phone'        => '登录手机',
    'goods'        => [ // 合并单元格
        'goods_name'  => '商品名称',
        'create_time' => '获取日期',
    ],
    'first_visit'  => '首次登录',
    'latest_visit' => '最近访问',
    'is_forbidden' => '是否限制登录',
], $data, '用户列表', 'goods');

```

<h3 id="request">request</h3>

当你使用本包的`Request`进行依赖注入，或者替代TP的`Controller`基类中的`request`使用时，就可以通过`$request->param_name`来获取前端表单传来的值，但是相对TP原生的`Request`会缺少一些东西，例如无法获取当前控制器名称等,`$request->->controller()`将会输出空串，可能还会有一些其他的坑吧。

<h3 id="model">模型 Model</h3>

以下是本包的`Model`特性
> `Model::query()->where()...`  这种写法可以使IDE的提示更友好，但是TP本身的模型就有问题，一些IDE提示的方法不见得能正常工作，血压也不用太高，大部分还是没问题的。
> `Model::query('string or array')` 也支持传入参数，可以去除对应的全局查询条件

> `Model::updateOrCreate($conditions,$update)` 

> `$model->saveQuietly()` 静默保存，不触发`AutoFingerPrint`中的事件

> `$model->load('your_relation')` `$model->loadMissing('your_relation.your_other_relation')` 动态加载关系，参考Laravel的，但是没有Laravel的强

> `Model::with('relation:id,name,other_column')` 加载指定关系并指定取其字段

> `$model->forceSave($data)` 支持更新已被软删除的数据，TP不能用save方法更新软删的数据💩💩💩

支持的属性

```php
/**
 * 商品Model
 */
class Goods extends \CherryLu\TpSupport\Model\BaseModel {

    public $modelName = '商品'; // 当有异常需要抛出时，可以用这个属性使消息更具可读性

    /**
    * 追加参数，每次查询出模型数据后，都会在attr中追加此数组中key对应的值，也是参考的Laravel 
    * $append 属性依赖于获取器
    * @var string[] 
    */ 
    protected $append = [
        'available',
    ];
    
    /** $append 属性依赖于获取器 */
    public function getAvailableAttr() {
        return !$this->delete_time;
    }
    
    public function itemInfo(){
        /** relationWithOutGlobalScope 传入想要去掉的关联模型(此处为Item模型)的全局查询条件，不传，则去掉全部的 */
        /** 在TP，模型如果加了全局查询条件 $globalScope， 就再也去不掉了，导致在关联的时候也会有这个限制 */
        return $this->relationWithOutGlobalScope(['scop_name'])->hasMany(Item::class);
    }
    
    public function users(){
        /** 在TP中，被软删的数据使用 belongsToMany 是无法加载的，所以请用这个吧 */
        /** 例如此处的User，如果数据被软删了，使用 belongsToMany 加 removeOption 也是无法关联到的 */
        return $this->cherryBelongsToMany(User::class, UserGoods::class, 'your_column', 'your_column', true);
    }
    
    /**
     * 版本 >= 1.1
     * 一对多关联，用于一个字段存贮多个id并用逗号隔开时使用
     * 一些不喜欢建中间表的朋友，可以用这个 cherryHasMany 来进行一对多的关联
     * 查询结果结构与 hasMany 一样的 
     * 
     * goods表: 
     * id   name    type_id 
     * 1    商品1    1,2
     * 1    商品2    3,4
     * 
     * type表:
     * id   name
     * 1    分类1
     * 2    分类2 
     * 3    分类3
     * 4    分类4
     */
    public function types(){
        /** 此关联不可用于hasWhere检索 */
        $this->cherryHasMany(TypeModel:class, 'id', 'type_id');
    }
    
}
```


<h3 id="migration">数据迁移 migration</h3>

TP 的集成的`Migrate`可以改为集成本包中的类，使IDE提示更友好，另外也增加了 `addFingerPrint` 方法可以快速定义<a href="#change-history">`AutoFingerPrint`</a>所需要的字段

```php

class CreateGoodsTable extends \CherryLu\TpSupport\Migrate\MineMigrator
{
    public function change()
    {
        $this->table('goods')
             ->setComment('商品表')
             ->setEngine('InnoDB')
             ->addColumn('name', 'string', ['comment' => '名称标题'])
             ->addFingerPrint() // 模型AutoFingerPrint需要的字段
             ->addIndex('name')
             ->create();
    }
}

```

<h3 id="change-history">数据修改记录 AutoFingerPrint</h3>

> 使用此功能前，必须运行包内的<a href="#tips">`1_create_change_history_table`</a>迁移文件
```php
class Goods extends \CherryLu\TpSupport\Model\BaseModel {

    /** 引入 AutoFingerPrint trait 每当使用模型进行修改时都会创建修改历史了，数据存在 change_history 表中*/
    use CherryLu\TpSupport\Model\AutoFingerPrint; 
}

/** 使用 saveQuietly 修改即可不触发AutoFingerPrint事件 */
$model->saveQuietly();

```

引入此模块后，将会自动维护数据表中的 `create_time` `create_by` `update_time` `update_by` `delete_time` `delete_by` 字段，
并会在`change_history`表中保存数据修改前后的快照

使用`$model->saveQuietly()`保存，可以不触发`AutoFingerPrint`事件
