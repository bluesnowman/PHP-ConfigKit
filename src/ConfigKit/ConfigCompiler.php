<?php
/**
 *  use ConfigKit\ConfigCompiler;
 *  $compiled = ConfigCompiler::compile('source_file.yml' , 'compiled_file.php');
 *  $config = ConfigCompiler::load('source_file.yml', 'compiled_file.php');
 *  $config = ConfigCompiler::load('source_file.yml');
 */
namespace ConfigKit;
use Exception;

class ConfigFileException extends Exception {  }

class ConfigCompiler
{
    public static function _compile_file($sourceFile,$compiledFile) {
        $content = file_get_contents($sourceFile);
        if( strpos($content,'---') === 0 ) {
            $config = yaml_parse($content);
        } 
        elseif(strpos($content,'<?php') === 0 ) {
            $config = require $sourceFile;
        } 
        elseif(strpos($content,'{') === 0 ) {
            // looks like a JSON
            $config = json_decode($content);
        } 
        else {
            throw new ConfigFileException('Unknown file format.');
        }

        self::write_config($compiledFile,$config);
        return $config;
    }

    public static function write_config($compiledFile, $config)
    {
        if( file_put_contents( $compiledFile , '<?php return ' . var_export($config,true) . ';' ) === false ) {
            throw new ConfigFileException("Can not write config file.");
        }
    }

    public static function compile($sourceFile,$compiledFile = null) { 
        if( ! $compiledFile ) {
            $p = strrpos($sourceFile,'.yml');
            if( $p === false ) {
                $p = strrpos($sourceFile,'.json');
            }
            // if file extension is not supported, just append the .php suffix
            if( $p === false ) {
                $compiledFile = $sourceFile . '.php';
            } else {
                $compiledFile = substr($sourceFile,0,$p) . '.php';
            }
        }
        if( ! file_exists($compiledFile) 
            || (file_exists($compiledFile) && filemtime($sourceFile) > filemtime($compiledFile))
            ) {
            self::_compile_file($sourceFile,$compiledFile);
        }
        return $compiledFile;
    }

    public static function load($sourceFile,$compiledFile = null) {
        $file = self::compile($sourceFile,$compiledFile);
        return require $file;
    }

    public static function unlink($sourceFile,$compiledFile = null) {
        $file = self::compile($sourceFile,$compiledFile);
        return unlink($file);
    }
}
