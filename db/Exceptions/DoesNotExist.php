<?php
namespace Enola\Db\Exceptions;

use Doctrine\ORM\NoResultException;

class DoesNotExist extends NoResultException{
    /**
     * Constructor.
     */
    public function __construct($prev = null)
    {
        parent::__construct('No result was found for query although at least one row was expected.', 0, $prev);
    }    
}
