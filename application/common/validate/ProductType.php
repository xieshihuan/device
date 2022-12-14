<?php
/**
 * +----------------------------------------------------------------------
 * | 广告验证器
 * +----------------------------------------------------------------------
 *                      .::::.
 *                    .::::::::.            | AUTHOR: siyu
 *                    :::::::::::           | EMAIL: 407593529@qq.com
 *                 ..:::::::::::'           | QQ: 407593529
 *             '::::::::::::'               | WECHAT: zhaoyingjie4125
 *                .::::::::::               | DATETIME: 2019/03/07
 *           '::::::::::::::..
 *                ..::::::::::::.
 *              ``::::::::::::::::
 *               ::::``:::::::::'        .:::.
 *              ::::'   ':::::'       .::::::::.
 *            .::::'      ::::     .:::::::'::::.
 *           .:::'       :::::  .:::::::::' ':::::.
 *          .::'        :::::.:::::::::'      ':::::.
 *         .::'         ::::::::::::::'         ``::::.
 *     ...:::           ::::::::::::'              ``::.
 *   ```` ':.          ':::::::::'                  ::::..
 *                      '.:::::'                    ':'````..
 * +----------------------------------------------------------------------
 */
namespace app\common\validate;

use think\Validate;

class ProductType extends Validate
{
    protected $rule = [
        'id' => 'require',
        'title' => 'require',
        'sort' => 'require',
    ];
    protected $message  =   [
        'id.require' => 'id不存在',
        'title.require' => '名称不存在',
        'sort.require' => '排序不存在',
    ];
    protected $scene = [
        'add'   =>  ['title','sort'],
        'edit'  =>  ['id','title','sort'],
        'del'  =>  ['id'],
    ];
}