<?php
namespace CrimA\Route;

/**
 * Route 核心路由
 *
 * @author xuelin
 */
class Route
{

    CONST HTTP_GET = 1;

    CONST HTTP_POST = 2;

    CONST HTTP_PUT = 3;

    CONST HTTP_DELETE = 4;

    CONST HTTP_HEAD = 5;

    CONST HTTP_OPTION = 6;

    CONST HTTP_TRACE = 7;

    public static $HTTP_TYPE;

    /**
     *
     * @var array [http_type=>[0=>[rule,target],1....]];
     */
    protected static $RuleArr;

    /**
     * Execute after Router finished processing 后置操作 适用于路由分析结束后的一些操作 比如让dispatch介入
     *
     * @param \Closure $clouse            
     */
    public function AfterExcute(\Closure $closure)
    {
        return $closure();
    }

    /**
     * Add HTTP GET rules 添加一条HTTP GET 路由规则
     *
     * @param String $rule
     *            example / or /home/user or /user/#id or /user/{regx}
     * @param
     *            mixed target
     *            php class namespace or Closule or null
     * @param
     *            mixed TargetMethod
     *            null or in TargetClass Method
     */
    public function GET(String $rule, $target, $TargetMethod = null)
    {}

    /**
     * Add a rule 添加一条路由规则
     *
     * @param $HttpType GET|POST|DELETE|PUT|HEAD            
     * @param
     *            mixed target
     *            php class namespace or Closule or null
     * @param
     *            mixed TargetMethod
     *            null or in TargetClass Method
     * @return void
     */
    public function AddRule(String $HttpType, String $rule, $target, $TargetMethod = null)
    {
        if (! is_null($TargetMethod)) {
            Route::$RuleArr[$HttpType][] = [
                $rule,
                $target,
                $TargetMethod
            ];
        } else {
            
            Route::$RuleArr[$HttpType][] = [
                $rule,
                $target
            ];
        }
    }

    /**
     * Analyse HTTP request And set related properties 分析HTTP请求 设置相关类属性
     *
     * @return CONSET HTTP_
     */
    public function AnalyseHTTP()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
                
                Route::$HTTP_TYPE = Route::HTTP_POST;
            } elseif (strtoupper($_SERVER['REQUEST_METHOD']) == 'PUT') {
                
                Route::$HTTP_TYPE = Route::HTTP_PUT;
            } elseif (strtoupper($_SERVER['REQUEST_METHOD']) == 'DELETE') {
                
                Route::$HTTP_TYPE = Route::HTTP_DELETE;
            } elseif (strtoupper($_SERVER['REQUEST_METHOD']) == 'OPTION') {
                
                Route::$HTTP_TYPE = Route::HTTP_OPTION;
            } elseif (strtoupper($_SERVER['REQUEST_METHOD']) == 'TRACE') {
                
                Route::$HTTP_TYPE = Route::HTTP_TRACE;
            } elseif (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') {
                
                Route::$HTTP_TYPE = Route::HTTP_HEAD;
            }
        }
    }

    public function run()
    {
        Route::AnalyseHTTP();
        
        $RuleArr = Route::$RuleArr[Route::$HTTP_TYPE];
        
        $UrlTarget = $url = $_SERVER['REQUEST_URI'];
        
        // 首先遍历查看是否符合其中某一条规则
        $ParamRuleArr = [];
        $RegxRuteArr = [];
        $PathRuteArr = [];
        
        // 分类规则，建议后期缓存路由规则与命中
        foreach ($RuleArr as $key => $value) {
            
            if (strpos($value['0'], '#') !== FALSE) {
                $ParamRuleArr[] = $RuleArr[$key];
                continue;
            }
            
            if (strpos($value['0'], '{') !== FALSE) {
                $RegxRuleArr[] = $RuleArr[$key];
                continue;
            }
            
            $PathRuteArr[] = $RuleArr[$key];
        }
        
        // first processing path
        foreach ($PathRuteArr as $key => $value) {
            
            if ($value['0'] == $UrlTarget) {
                
                return Route::RulePath($ParamRuleArr[$key]);
            }
        }
        
        // second params /user/#id#/ablum/#ablum_id# /user/1/ablum/5
        
        // 首先去把pathrule全部转换为正则表达式 比如 /user/#id#/ablum/#ablum_id# 变成 #^/user/([\d\w_-]{1,20})/ablum/([\d\w_-]{1,20})$# 然后对/user/1/ablum/5进行正则匹配 如果符合命中就返回
        
        // 问题？性能？
        
        // 获取url中对应的 /user/#id#/ablum/#ablum_id# /user/1/ablum/5
        // 首先去获取路由规则和正则表达式的substr_count(url,'/')数，
        // 然后去比较是否相等，等同则 explode(url,'/') explode(rule,'/'),对url数组中代替#ablum_id#之类转换为与rule数组中相等的值，再比较两个数组是否相等
        // 如果相等则匹配成功
        // 暂时(或者永远)不支持/user-1-ablum-2 等以非/为分隔符的规则，当然也不支持 /user/#id#/#h#-#b# 推荐直接使用正则规则进行匹配 为了性能(*^__^*)
        
        $UrlDelimiterArr = explode('/', $url);
        $UrlDelimiterCount = count($UrlDelimiterArr);
        
        $RuleArr = [];
        
        foreach ($ParamRuleArr as $value) {
            
            if ((substr_count('/', $value[0]) + 1) == $UrlDelimiterCount) { // if equal can next;
                
                $RuleArr = explode('/', $value[0]);
                
                foreach ($RuleArr as $key => $value) { // 将rulearr中包含#的规则value转换为url同key的value 注意准备将其装载到需要的变量中
                    if (strpos($value, '#') !== FALSE) {
                        $RuleArr[$key] = $UrlDelimiterArr[$key];
                    }
                }
                
                if ($RuleArr === $UrlDelimiterArr) { //进过上面的对应替换如果规则符合则两数组长度、各key-value 一致
                    
                    return Route::RuleParams($RuleArr);//此处略
                }
                
            }
        }
        
        
        //third regx rule /user/regx:/^(\d)$/}/{^[a-z_]{1,5}$}   
        
        foreach ($RegxRuteArr as $key => $value) {
             
        }
        
        
        
    }

    /**
     * processing path rule 处理Path规则 example rule: [/user/info,\app\d\home or Clouse]
     *
     * @param array $rule
     *            [rule,target]
     * @return call_user_func params array(array('class','method'), array("param1" ...))
     */
    protected static function RulePath(array $rule)
    {
        if (is_callable($rule[1])) {
            
            return $rule[1]();
        } else {}
        // 已经命中这条规则符合pathinfo
    }

    protected function RuleParams(array $rule)
    {}
 
    protected function RuleRegx()
    {}
    
    
}

