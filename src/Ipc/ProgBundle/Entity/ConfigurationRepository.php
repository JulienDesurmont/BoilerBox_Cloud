<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ConfigurationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ConfigurationRepository extends EntityRepository
{
    public function myFindCourant()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
        	->select('s')
            ->from($this->_entityName,'s')
            ->where('s.siteCourant = :courant')
            ->setParameter('courant',true);
        $query = $queryBuilder->getQuery();
        try {
            $result = $query ->getSingleResult();
        } catch (\Doctrine\Orm\NoResultException $e) {
            $result = null;
        }
        return $result;
    }

	public function myFindOneByParametreLike($parametre) {
		$parametre = $parametre.'%';
		$qb = $this->createQueryBuilder('c');
		$qb->where($qb->expr()->like('c.parametre',':parametre'))
			->setParameter('parametre', $parametre);
		try {
            $result = $qb->getQuery()->getArrayResult();
        } catch (\Doctrine\Orm\NoResultException $e) {
            $result = null;
        }
        return $result;
	}


	public function getValueOf($parametre){
		$qb = $this->createQueryBuilder('c');
		$qb->select('c.valeur')
		->where('c.parametre = :parametre')
		->setParameter('parametre', $parametre);
		return $qb->getQuery()->getSingleScalarResult();
	}
}