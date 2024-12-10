<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureService
{

    public function __construct(
        private ParameterBagInterface $params
    )
    {
    }

    /**
     * @throws Exception
     */
    public function square(UploadedFile $picture, ?string $folder = '', ?int $width = 250, ?int $height = 250): string
    {

        $file = md5(uniqid(rand(), true)) . '.webp';
        $pictureInfo = getimagesize($picture);

        if ($pictureInfo === false) {
            throw new Exception('Format d\'image non valide');
        }

        match ($pictureInfo['mime']) {
            'image/png' => $sourcePicture = imagecreatefrompng($picture),
            'image/webp' => $sourcePicture = imagecreatefromwebp($picture),
            'image/jpeg' => $sourcePicture = imagecreatefromjpeg($picture),
            default => throw new Exception('Format d\'image non valide')
        };

        $imageWidth = $pictureInfo[0];
        $imageHeight = $pictureInfo[1];

        switch ($imageWidth <=> $imageHeight) {
            case -1:
                $squareSize = $imageWidth;
                $srcX = 0;
                $srcY = ($imageHeight - $imageWidth) / 2;
                break;
            case 0:
                $squareSize = $imageWidth;
                $srcX = 0;
                $srcY = 0;
                break;
            case 1:
                $squareSize = $imageHeight;
                $srcX = ($imageWidth - $imageHeight) / 2;
                $srcY = 0;
                break;

        }

        $resizedPicture = imagecreatetruecolor($width, $width);

        imagecopyresampled($resizedPicture, $sourcePicture, 0, 0, $srcX, $srcY, $width, $width, $squareSize, $squareSize);

        $path = $this->params->get("uploads_directory") . $folder;

        if (!file_exists($path . '/mini/')) {
            mkdir($path . '/mini/', 0755, true);
        }

        imagewebp($resizedPicture, $path . '/mini/' . $width . 'x' . $height . '-' . $file);
        $picture->move($path . '/', $file);

        return $file;
    }


    public function delete(string $fichier, ?string $folder = '', ?int $width = 250, ?int $height = 250): string
    {
        if ($fichier !== 'default.webp') {
            $success = false;
            $path = $this->params->get("uploads_directory") . $folder;
            $mini = $path . '/mini/' . $width . 'x' . $width . '-' . $fichier;
            if (file_exists($path . '/mini/')) {
                unlink($mini);
                $success = true;
            }

            $original = $path . '/original/' . '/' . $fichier;
            if (file_exists($original)) {
                unlink($mini);
                $success = true;
            }
            return $success;
        }
        return false;
    }
}