<?php
namespace Enola\Db\Models;

trait ModelDb {
    
    public static function pk()
    {
        return isset(self::$pk) ? self::$pk : 'id';
    }
    
    public static function connection()
    {
        return isset(self::$connection) ? self::$connection : null;
    }
    
    public abstract static function db();
    
    public abstract function save();
    
    public abstract function destroy();
}
