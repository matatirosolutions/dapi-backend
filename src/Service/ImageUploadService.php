<?php


namespace App\Service;

use \Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploadService
{

    /** @var FileMakerAPI */
    protected $fm;

    /**
     * ImageUploadService constructor.
     * @param FileMakerAPI $fm
     */
    public function __construct(FileMakerAPI $fm)
    {
        $this->fm = $fm;
    }

    /**
     * @param UploadedFile $file
     *
     * @return array
     *
     * @throws Exception
     */
    public function imageUpload(UploadedFile $file)
    {
        // Save the file into the temp directory so that it's accessible to upload - we need to do this
        // so that it ends up in the FileMaker container with the correct name. If we just try and upload
        // the temporary file, or the data stream then FileMaker doesn't get the correct filename and
        // so the container won't display correctly.
        // For simplicity we just drop it into the system temp directory
        $destination = sys_get_temp_dir(). DIRECTORY_SEPARATOR . $file->getClientOriginalName();
        $file->move(sys_get_temp_dir(), $file->getClientOriginalName());

        // Create a file record to attach the upload to
        $record = $this->createImageRecord();

        // Call the method to insert the file into the container
        $this->fm->performContainerInsert('ImagesAPI', $record['recordId'], 'Image', $destination);

        // Delete the file we don't need it here it's now safely in the FileMaker container. This isn't
        // strictly necessary because it's in the temp folder so the OS will tidy it up over time
        unlink($destination);

        // Return the record that we created
        return $record;
    }


    /**
     * @return array
     *
     * @throws Exception
     */
    private function createImageRecord()
    {
        // To create a record you have to POST fieldData, even though we don't want any, AND it has to be
        // and object, so we cast an empty array as an object.
        $body = [
            'fieldData' => (object)[]
        ];

        // The body always needs to exist, and it always needs to be JSON, so encode that
        $params = ['body' => json_encode($body)];

        return $this->fm->performRequest('POST', 'layouts/ImagesAPI/records', $params);
    }
}