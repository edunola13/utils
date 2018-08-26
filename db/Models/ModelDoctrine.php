<?php
namespace Enola\Db\Models;

use Enola\Db\Doctrine\DoctrineHelper;
use Enola\Db\Models\ModelDb;
use Enola\Db\Doctrine\EnolaQueryBuilder;

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
            self::$db = new DoctrineHelper(self::class, ['pk' => self::$pk, 'connection' => self::$connection]);
        }
        return self::$db;
    }
    
    public function save() {
        self::db()->save($this);
    }
    
    public function destroy() {
        self::db()->remove($this);
    }
    /** 
     * Query prearmada con el modelo actual
     * @param string[] $with
     * @return EnolaQueryBuilder
     */
    public function query($with = []) {
        return self::db()->queryBuilder()->with($with);
    }
}
