<?php
namespace Enola\Db\Models;

use Enola\Db\Doctrine\DoctrineHelper;
use Enola\Db\Models\ModelDb;

trait ModelDoctrine {
    use ModelDb;
    /**
     * @var DoctrineHelper
     */
    public static $db;
    /** 
     * @return DoctrineHelper
     */
    public static function db() {
        if (! self::$db) {
            self::$db = new DoctrineHelper(self::class, ['pk' => self::$pk]);
        }
        return self::$db;
    }
    
    public function save() {
        self::db()->save($this);
    }
    
    public function destroy() {
        self::db()->remove($this);
    }
}
