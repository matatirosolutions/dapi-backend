<?php

namespace App\Controller;

use \Exception;
use App\Service\ImageUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ImageUploadController extends AbstractController
{
    /**
     * @Route("/file/upload", name="file_upload")
     *
     * @param Request $request
     * @param ImageUploadService $uploadService
     *
     * @return JsonResponse
     */
    public function index(Request $request, ImageUploadService $uploadService): JsonResponse
    {
        try {
            // Get the file object (UploadedFile) from the request
            $file = $request->files->get('file');

            // And pass that through to the ImageUpload service
            $uploadService->imageUpload($file);

            // If nothing goes wrong, return a success message
            return new JsonResponse(['success' => true]);
        } catch(Exception $e) {
            // If anything goes wrong return an error HTTP status code - this will tell the uploader
            // to display an 'x' error message.
            return new JsonResponse('Unable to upload file: '. $e->getMessage(), 500);
        }
    }
}
