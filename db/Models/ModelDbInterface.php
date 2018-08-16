<?php
namespace Enola\Db\Models;

interface ModelDbInterface {
    
    public static function db();
    
    public function save();
    
    public function destroy();
}
