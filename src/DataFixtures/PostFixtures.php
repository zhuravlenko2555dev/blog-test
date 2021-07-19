<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class PostFixtures extends Fixture
{
    private $faker;
    private $categoryRepository;
    private $userRepository;

    public function __construct(CategoryRepository $categoryRepository, UserRepository $userRepository)
    {
        $this->faker = Factory::create();
        $this->categoryRepository = $categoryRepository;
        $this->userRepository = $userRepository;
    }

    public function load(ObjectManager $manager)
    {
        $categories = $this->categoryRepository->findAll();

        for ($i = 0; $i < 40; $i++) {
            $post = new Post();
            $post->setTitle($this->faker->unique()->text($this->faker->numberBetween(5, 200)));
            $post->setBody($this->faker->unique()->text($this->faker->numberBetween(10, 300)));
            $post->setUser($this->userRepository->find($this->faker->numberBetween(1, 10)));

            $categoriesCount = $this->faker->numberBetween(1, 10);
            $categoriesSelected = array_rand($categories, $categoriesCount);
            if (is_array($categoriesSelected)) {
                foreach ($categoriesSelected as $category) {
                    $post->addCategory($categories[$category]);
                }
            } else {
                $post->addCategory($categories[$categoriesSelected]);
            }

            $manager->persist($post);
        }

        $manager->flush();
    }
}
