<?php
/**
 * Suda FrameWork
 *
 * An open source application development framework for PHP 7.0.0 or newer
 * 
 * Copyright (c)  2017 DXkite
 *
 * @category   PHP FrameWork
 * @package    Suda
 * @copyright  Copyright (c) DXkite
 * @license    MIT
 * @link       https://github.com/DXkite/suda
 * @version    since 1.2.4
 */
namespace suda\template\compiler;

use Storage;
use suda\core\Application;
use suda\tool\Value;
use suda\template\Compiler;
use suda\template\Manager;

/**
 *
 */
class SudaCompiler implements Compiler
{
    protected static $rawTag=['{{!','}}'];
    protected static $echoTag=['{{','}}'];
    protected static $hookTag=['{:','}'];
    protected static $commentTag=['{--','--}'];
    /**
     * 附加模板命令
     *
     * @var array
     */
    protected static $command=[];
    
    // 编译文本
    public function compileText(string $text)
    {
        $result='';
        foreach (token_get_all($text) as $token) {
            if (is_array($token)) {
                list($tag, $content) = $token;
                // 所有将要编译的文本
                // 跳过各种的PHP
                if ($tag == T_INLINE_HTML) {
                    $content=self::compileString($content);
                    $content=self::compileCommand($content);
                    $content=self::echoValue($content);
                }
                $result .=$content;
            } else {
                $result .=$token;
            }
        }
        // 合并相邻标签
        $result=preg_replace('/\?\>(\s*?)\<\?php/i', '', $result);
        return $result;
    }

    /**
     * 编译文件
     * @param $input
     * @return mixed
     */
    public function compile(string $name, string $input, string $output)
    {
        _D()->time('compile '.$name);

        if (!Storage::exist($input)) {
            return false;
        }
        $content= $this->compileText(Storage::get($input));
        if (!Storage::isDir($dir=dirname($output))) {
            Storage::mkdirs(dirname($output));
        }

        $classname='Template_'.md5($name);
        $content='<?php  class '.$classname.' extends suda\template\compiler\suda\Template { protected $name="'.$name.'"; protected function _render_template() {  ?>'.$content.'<?php }}';
        Storage::put($output, $content);
        _D()->timeEnd('compile '.$name);
        return true;
    }

    public function render(string $name, string $viewfile)
    {
        $name='Template_'.md5($name);
        require_once $viewfile;
        return $template=new $name;
    }
    
    /**
     * 扩展模板命令
     *
     * @param string $name
     * @param string $callback
     * @param bool $echo
     * @return void
     */
    public static function addCommand(string $name, string $callback, bool $echo=true)
    {
        $name=ucfirst($name);
        self::$command[$name]=['command'=>$callback,'echo'=>$echo];
    }
    
    /**
     * 检查模板扩展命令是否存在
     *
     * @param string $name
     * @return bool
     */
    public static function hasCommand(string $name):bool
    {
        $name=ucfirst($name);
        return isset(self::$command[$name]);
    }

    /**
     * 创建模板扩展命令
     *
     * @param string $name
     * @param string $exp
     * @return string
     */
    public static function buildCommand(string $name, string $exp):string
    {
        $name=ucfirst($name);
        if (self::hasCommand($name)) {
            $echo=self::$command[$name]['echo']?'echo':'';
            $command=self::$command[$name]['command'];
            return '<?php '.$echo.' (new \suda\tool\Command("'.$command.'"))->args'. ($exp?:'()').' ?>';
        }
        return '';
    }

    private function compileString(string $str)
    {
        $callback=function ($match) {
            if (self::hasCommand(ucfirst($match[1]))) {
                $match[0]=self::buildCommand($match[1], $match[3] ?? '');
            } elseif (method_exists($this, $method = 'parse'.ucfirst($match[1]))) {
                $match[0] = $this->$method($match[3] ?? '');
            }
            return isset($match[3]) ? $match[0] : $match[0].$match[2];
        };
        // \x{4e00}-\x{9aff} 为中文字符集范围
        return preg_replace_callback('/\B@([\w\x{4e00}-\x{9aff}]+)(\s*)(\( ( (?>[^()]+) | (?3) )* \) )? /ux', $callback, $str);
    }

    private function compileCommand(string $str)
    {
        $echo=sprintf('/(?<!!)%s\s*(.+?)\s*?%s/', preg_quote(self::$echoTag[0]), preg_quote(self::$echoTag[1]));
        $rawecho=sprintf('/(?<!!)%s\s*(.+?)\s*?%s/', preg_quote(self::$rawTag[0]), preg_quote(self::$rawTag[1]));
        $comment=sprintf('/(?<!!)%s(.+)%s/', preg_quote(self::$commentTag[0]), preg_quote(self::$commentTag[1]));
        $hook=sprintf('/(?<!!)%s(.+)%s/', preg_quote(self::$hookTag[0]), preg_quote(self::$hookTag[1]));
        return self::echoValue(preg_replace(
            [$rawecho, $echo, $comment, $hook ],
            ['<?php echo $1; ?>', '<?php echo htmlspecialchars($1); ?>', '<?php /* $1 */ ?>', '<?php $this->execGloHook("$1") ?>'],
            $str
        ));
    }

    protected static function echoValue($var)
    {
        // 任意变量名(除空格,和字符串界定符号)
        return preg_replace_callback('/\B[$][:]([\w\x{4e00}-\x{9aff}]+)(\s*)(\( ( (?>[^()]+) | (?3) )* \) )?/ux', function ($matchs) {
            $name=$matchs[1];
            $args=isset($matchs[4])?','.$matchs[4]:'';
            return '$this->get("'.$name.'"'.$args.')';
        }, $var);
    }

    protected static function parseValue($var)
    {
        return '<?php echo $this->get'.self::echoValue($var) .'; ?>';
    }
    
    protected function parseEcho($exp)
    {
        return "<?php echo htmlspecialchars{$exp}; ?>";
    }

    protected function parseData($exp)
    {
        return "<?php \$this->data{$exp}; ?>";
    }
    
    // IF 语句
    protected function parseIf($exp)
    {
        return "<?php if{$exp}: ?>";
    }
    protected function parseEndif()
    {
        return '<?php endif; ?>';
    }
    protected function parseElse()
    {
        return '<?php else: ?>';
    }
    protected function parseElseif($exp)
    {
        return "<?php elseif {$exp}: ?>";
    }
    // for
    protected function parseFor($expression)
    {
        return "<?php for{$expression}: ?>";
    }
    protected function parseEndfor()
    {
        return '<?php endfor; ?>';
    }
    // foreach
    protected function parseForeach($exp)
    {
        return "<?php foreach{$exp}: ?>";
    }
    protected function parseEndforeach()
    {
        return '<?php endforeach; ?>';
    }
    // while
    protected function parseWhile($exp)
    {
        return "<?php while{$exp}: ?>";
    }

    protected function parseEndwhile()
    {
        return '<?php endwhile; ?>';
    }

    // include
    protected function parseInclude($exp)
    {
        preg_match('/\((.+)\)/', $exp, $v);
        $name=str_replace('\'', '-', trim($v[1], '"\''));
        return "<?php suda\\template\\Manager::display('{$name}')->parent(\$this)->assign(\$this->value)->render(); ?>";
    }

    protected function parseU($exp)
    {
        if ($exp==='') {
            $exp='()';
        }
        return "<?php echo u$exp; ?>";
    }
    
    protected function parseSelf()
    {
        return "<?php echo \$_SERVER['PHP_SELF'] ?>";
    }

    protected function parseSet($exp)
    {
        return "<?php \$this->set{$exp}; ?>";
    }
    
    protected function parseStatic($exp)
    {
        preg_match('/^\((.+?)\)$/', $exp, $match);
        $module=$match[1]??Application::getActiveModule();
        $module=trim($module, '"\'');
        $path=Manager::prepareResource($module);
        $static_url=Storage::cut($path, APP_PUBLIC);
        $static_url=preg_replace('/[\\\\\/]+/', '/', $static_url);
        return '/'.$static_url;
    }

    
    protected function parseUrl($exp)
    {
        return "<?php echo u{$exp}; ?>";
    }
    // View echo
    public static function echo($something)
    {
        foreach (func_get_args() as $arg) {
            echo htmlspecialchars($arg);
        }
    }

    protected function parseStartInsert($exp)
    {
        preg_match('/\((.+)\)/', $exp, $v);
        $name=str_replace('\'', '-', trim($v[1], '"\''));
        return '<?php $this->hook(\''.$name.'\',function () { ?>';
    }
    
    protected function parseEndInsert()
    {
        return '<?php });?>';
    }
    protected function parseInsert($exp)
    {
        preg_match('/\((.+)\)/', $exp, $v);
        $name=str_replace('\'', '-', trim($v[1], '"\''));
        return "<?php \$this->exec('{$name}'); ?>";
    }

    // 错误报错
    public function error()
    {
        return self::$error[self::$erron];
    }
    // 错误码
    public function erron()
    {
        return self::$erron;
    }
}
