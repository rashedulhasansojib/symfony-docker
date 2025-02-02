<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Faker\Factory;

class BookController extends AbstractController
{

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('book/index.html.twig');
    }


    #[Route('/books', name: 'get_books', methods: ['GET'])]
    public function getBooks(Request $request): JsonResponse
    {
        $language = $request->query->get('language', 'en_US');
        $seed = (int) $request->query->get('seed', random_int(1, 9999999));
        $likes = (float) $request->query->get('likes', 5.0);
        $reviews = (float) $request->query->get('reviews', 4.5);
        $page = (int) $request->query->get('page', 1);

        // Combine seed and page number
        $faker = Factory::create($language);
        $faker->seed($seed + $page);

        $books = [];
        for ($i = 1; $i <= 20; $i++) {
            $books[] = [
                'index' => (($page - 1) * 20) + $i,
                'isbn' => $faker->isbn13(),
                'title' => $faker->sentence(3),
                'author' => $faker->name(),
                'publisher' => $faker->company(),
                'likes' => round($likes),
                'reviews' => $this->generateReviews($faker, $reviews),
            ];
        }

        return new JsonResponse($books);
    }

    #[Route('/export-csv', name: 'export_csv')]
    public function exportCsv(Request $request): Response
    {
        $language = $request->query->get('language', 'en_US');
        $seed = (int) $request->query->get('seed', random_int(1, 9999999));
        $likes = (float) $request->query->get('likes', 5.0);
        $reviews = (float) $request->query->get('reviews', 4.5);
        $page = (int) $request->query->get('page', 1);

        $faker = Factory::create($language);
        $faker->seed($seed + $page);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Index', 'ISBN', 'Title', 'Author', 'Publisher', 'Likes', 'Reviews']);

        for ($i = 1; $i <= 20; $i++) {
            fputcsv($handle, [
                (($page - 1) * 20) + $i,
                $faker->isbn13(),
                $faker->sentence(3),
                $faker->name(),
                $faker->company(),
                round($likes),
                round($reviews),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="books.csv"',
        ]);
    }


    private function generateReviews($faker, $reviews)
    {
        $reviewCount = round($reviews);
        $reviewList = [];

        for ($i = 0; $i < $reviewCount; $i++) {
            $reviewList[] = [
                'reviewer' => $faker->name(),
                'text' => $faker->sentence(10),
            ];
        }

        return $reviewList;
    }
}
