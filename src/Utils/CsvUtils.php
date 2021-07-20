<?php


namespace App\Utils;


use App\Entity\Category;
use App\Entity\Post;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectManager;
use League\Csv\Reader;
use League\Csv\Writer;

class CsvUtils
{
    public function exportPosts(PostRepository $postRepository)
    {
        $header = [
            'title',
            'body',
            'categories',
            'user'
        ];
        $records = [];

        $posts = $postRepository->findAll();
        foreach ($posts as $post) {
            $row = [];
            $row['title'] = $post->getTitle();
            $row['body'] = $post->getBody();
            $row['categories'] = implode(',', $post->getCategories()->map(function(Category $category) {return $category->getId();})->getValues());
            $row['user'] = $post->getUser()->getId();

            array_push($records, $row);
        }

        $csv = Writer::createFromString();
        $csv->insertOne($header);
        $csv->insertAll($records);
        return $csv->toString();
    }

    public function importPosts(string $path, ObjectManager $entityManager, CategoryRepository $categoryRepository, UserRepository $userRepository)
    {
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $header = $csv->getHeader();
        $records = $csv->getRecords();

        foreach ($records as $record) {
            $post = new Post();
            $post->setTitle($record['title']);
            $post->setBody($record['body']);
            foreach (explode(',', $record['categories']) as $category) {
                $post->addCategory($categoryRepository->find($category));
            }
            $post->setUser($userRepository->find($record['user']));

            $entityManager->persist($post);
        }

        $entityManager->flush();
    }
}