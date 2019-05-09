<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Song;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Album|null find($id, $lockMode = null, $lockVersion = null)
 * @method Album|null findOneBy(array $criteria, array $orderBy = null)
 * @method Album[]    findAll()
 * @method Album[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Album::class);
    }

    // /**
    //  * @return Album[] Returns an array of Album objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Album
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getGenres(int $albumId)
    {
        $songsInAlbum = $this->getEntityManager()->getRepository(Song::class)->findBy(['album' => $albumId]);
        $genres = [];
        foreach ($songsInAlbum as $song) {
            if (!in_array($song->getGenre(), $genres)) {
                $genres[] = $song->getGenre();
            }
        }
        return $genres ?? [];
    }

    public function getTracklist(int $albumId)
    {
        $songsInAlbum = $this->getEntityManager()->getRepository(Song::class)->findBy(['album' => $albumId]);
        foreach ($songsInAlbum as $song) {
            $tracks[$song->getTrackNumber()] = $song->getTitle();
        }
        return $tracks ?? [];
    }


    public function getAlbumsByParameters(string $query)
    {
        $manager = $this->getEntityManager();
        $criteria = explode('&', $query);
        $params = [];
        $finalQueryParameters = [];
        foreach ($criteria as $criterium) {
            if (isset(explode('=', $criterium)[1]) && explode('=', $criterium)[1] !== '') {
                $params[explode('=', $criterium)[0]] = explode('=', $criterium)[1];
            }
        }
        $finalQuery = "SELECT albums FROM App\Entity\Album albums";
        $finalQuery .= " JOIN App\Entity\Artist artists";
        $finalQuery .= " JOIN App\Entity\Song songs";
        $finalQuery .= " WHERE albums.artist = artists.id";
        $finalQuery .= " AND albums.id = songs.album";
        if (array_key_exists('artist', $params)) {
            $finalQuery .= ' AND artists.name like :artistName';
            $finalQueryParameters['artistName'] = '%' . $params['artist'] . '%';
        }
        if (array_key_exists('country', $params)) {
            $finalQuery .= ' AND artists.country like :country';
            $finalQueryParameters['country'] = '%' . $params['country'] . '%';
        }
        if (array_key_exists('genre', $params)) {
            $finalQuery .= ' AND songs.genre like :genre';
            $finalQueryParameters['genre'] = '%' . $params['genre'] . '%';
        }
        if  (array_key_exists('yearFrom', $params)) {
            $finalQuery .= " AND albums.releaseYear >= :yearFrom";
            $finalQueryParameters['yearFrom'] = $params['yearFrom'];
        }
        if  (array_key_exists('yearTo', $params)) {
            $finalQuery .= " AND albums.releaseYear <= :yearTo";
            $finalQueryParameters['yearTo'] = $params['yearTo'];
        }


        $data = $manager->createQuery($finalQuery);

        $data->setParameters($finalQueryParameters);

        return $data->execute();

    }

}
