<?php
namespace Enola\Db\Models;

trait ModelDb {
    public static $pk = 'id';
    public static $connection = null;
    
    public abstract static function db();
    
    public abstract function save();
    
    public abstract function destroy();
}
