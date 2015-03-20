<?php

namespace Stems\BlogBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Snc\RedisBundle\Doctrine\Cache\RedisCache;
use Redis;

class PostRepository extends EntityRepository
{
	/** 
	 * Get the latest posts
	 *
	 * @param  integer 	$limit
	 * @param  integer 	$offset
	 * @param  boolean 	$forWidget
	 * @return array 					The resulting posts
	 */
	public function findLatest($limit = 5, $offset = 0, $forWidget = false) 
	{
		$qb = $this->getEntityManager()->createQueryBuilder();
		$qb->addSelect('post');
		$qb->from('StemsBlogBundle:Post', 'post');
		
		// Set parameters
		$qb->where('post.deleted = :deleted');
		$qb->andWhere('post.status = :status');
		$qb->setParameter('deleted', '0');
		$qb->setParameter('status', 'Published');

		// Filter those hidden for widget, if specified
		if ($forWidget) {
			$qb->andWhere('post.hideFromWidgets = :hideFromWidgets');
			$qb->setParameter('hideFromWidgets', false);
		}

		// Order by most recently publishsed
		$qb->orderBy('post.created', 'DESC');	

		// Execute the query
		return $qb
			->setMaxResults($limit)
			->getQuery()
			// ->setResultCacheDriver($redis = $this->loadRedis())
			// ->setResultCacheLifetime(86400)
			->getResult();
	}

	public function findLatestForWidget($limit, $offset = 0)
	{
		return $this->findLatest($limit, $offset, true);
	}

	protected function loadRedis() 
	{
		$cache = new RedisCache();
		$cache->setRedis(new Redis());

		return $cache;
	}
}