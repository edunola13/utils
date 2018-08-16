<?php
namespace Enola\Lib\Pagination;

use JsonSerializable;
/**
 * Interface para las clases que quieran paginar resultados
 * @author Eduardo Sebastian Nola <edunola13@gmail.com>
 * @category Enola\Lib\Pagination
 * @version 1.0
 */
interface PaginationInterface {
    public function __construct($amountPerPage, $totalAmount, $currentPage, $startPosition = 0);
    /**
     * Retorna la cantidad de paginas
     * @return int
     */
    public function numberOfPages();
    /**
     * Retorna la posicion del elemento de inicio de la pagina actual.
     * @return int
     */
    public function elementStartPosition();
    /**
     * Retorna la posicion del elemento de fin de la pagina actual.
     * @return int
     */
    public function elementEndPosition();
    /**
     * Retorna la pagina anterior o null en caso de que no haya anterior
     * @return int
     */
    public function previousPage();
    /**
     * Retorna la pagina siguiente o null en caso de que no haya siguiente
     * @return int
     */
    public function nextPage();
    /**
     * Retorna si la pagina indicada es la actual
     * @param int $page
     * @return bool
     */
    public function isActualPage($page);
}