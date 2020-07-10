<?php

namespace Maze\MazeTv\Repository;

use Doctrine\ORM\EntityRepository;

class MazeStreamerRepository extends EntityRepository
{
    // public function findAll(): array
    // {
    //     $result = Db::getInstance()->executeS('SELECT id_manufacturer FROM `' . _DB_PREFIX_ . 'mazetv_manufacturer_streamer`');
    //     if ($result) {
    //         PrestaShopLogger::addLog('Failed to retrieve mazetv manufacturers:' . Db::getInstance()->getMsgError(), 4);
    //     }

    //     $manufacturers = [];
    //     foreach ($result as $row) {
    //         $manufacturers[] = new Manufacturer($row['id_manufacturer']);
    //     }

    //     return $manufacturers;
    // }
}
