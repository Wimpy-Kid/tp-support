让 ThinkPHP 好用一点，对IDE更友好，也更贴近 Laravel 框架的习惯；
虽然也还是很烂，尽力了🤣。为了不让你心情更堵，不建议使用本包。

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
安装命令 `composer require CherryLu/tp-support`

安装完成后如果你想用到本包的<a href="#change-history">`AutoFingerPrint`</a>模块，请将包内的`1_create_change_history_table`文件复制到项目的`migrations`文件夹，并在项目根目录运行`php think migrate:run`命令。


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
    
        /** 也可以只定义各个表单的名称，会自动返回对应的验证信息 */
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
        'nick_name'    => '用户昵称',
        'phone'        => '登录手机',
        'goods'        => [ // 合并单元格
            'goods_name'  => '商品名称',
            'create_time' => '获取日期',
        ],
        'first_visit'  => '首次登录',
        'latest_visit' => '最近访问',
        'is_forbidden' => '是否限制登录',
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

本来作为一个毫无感情的数据库查询机器，什么大风大浪没见过，但是TP的模型查询可以让我直接血压飙升，确实很佩服他了；

以下是本包的`Model`特性
>   `Model::query()->where()...`  这种写法可以使IDE的提示更友好，但是TP本身的模型就有问题，一些IDE提示的方法不见得能正常工作，血压也不用太高，大部分还是没问题的
；

> `Model::withTrashed()` 包含软删除数据的查询，这个TP原生的💩方法，记得一定要写在所有查询条件的最前面

> `Model::updateOrCreate($conditions,$update)` 和Laravel的一样，相比TP的`save`描述更语义化，

> `$model->saveQuietly()` 静默保存，不触发`AutoFingerPrint`中的事件，也是参考了Laravel

> `$model->load('your_relation')` `$model->loadMissing('your_relation')` 动态加载关系，参考Laravel的，但是没有Laravel的强，只能支持单层关系加载

> `Model::with('relation:id,name,other_column')` 加载指定关系并指定取其字段，只支持单层关系加载，多层的还是用TP原生吧

> `$model->forceSave($data)` 更新已被软删除的数据，你敢信，TP居然不能用模型更新软删的数据💩💩💩

支持的属性
```php
class Goods extends \CherryLu\TpSupport\Model\BaseModel {

    public $modelName = '商品'; // 当有异常需要抛出时，可以用这个属性使消息更具可读性

    /**
    * @var string[] 追加参数，每次查询出模型数据后，都会在attr中追加此数组中key对应的值，也是参考的Laravel 
    */ 
    protected $append = [
        'available',
    ];
    
    public function getAvailableAttr() {
        return !$this->delete_time;
    }
    
    public function itemInfo(){
        /** relationWithOutGlobalScope 传入想要去掉的Item模型的全局查询条件，不传，则去掉全部的 */
        /** 在TP，模型如果加了全局查询条件 $globalScope， 就再也去不掉了，导致在关联的时候也会有这个限制 */
        return $this->relationWithOutGlobalScope(['scop_name'])->hasMany(Item::class);
    }
    
    public function users(){
        /** 在TP中，被软删的数据使用 belongsToMany 是无法加载的，所以请用这个吧 */
        /** 例如此处的User，如果数据被软删了，使用 belongsToMany 加 removeOption 也是无法关联到的 */
        return $this->cherryBelongsToMany(User::class, UserGoods::class, 'your_column', 'your_column', true);
    }
    
}
```


<h3 id="migration">数据迁移 migration</h3>

TP 的集成的`Migrate`可以改为集成本包中的类，使IDE提示更友好，另外也增加了 `addFingerPrint` 方法可以快速定义`AutoFingerPrint`所需要的字段

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

<h3 id="change-history">数据修改记录 </h3>

```php
class Goods extends \CherryLu\TpSupport\Model\BaseModel {

    /** 引入 AutoFingerPrint trait 每当使用模型进行修改时都会创建修改历史了，数据存在 change_history 表中*/
    use \CherryLu\TpSupport\Model\AutoFingerPrint; 
}

/** 使用 saveQuietly 修改即可不触发此事件 */
$model->saveQuietly();

```
