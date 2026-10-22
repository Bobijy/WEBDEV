<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\QueryBuilder;

final class PaginationService
{
    public function paginate(QueryBuilder $query, int $page, int $pageSize = 24): array
    {
        $alias = $query->getRootAliases()[0];
        $total = (int) (clone $query)->resetDQLPart('orderBy')->select('COUNT(DISTINCT ' . $alias . '.id)')
            ->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / $pageSize));
        $page = max(1, min($page, $pages));

        return [
            'items' => $query->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize)->getQuery()->getResult(),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ];
    }
}
