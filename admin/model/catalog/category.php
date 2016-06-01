<?php
use scripts\Itul\SVGReader;

class ModelCatalogCategory extends Model {

    /*
        A bunch of OpenCart methods already existed here, I omitted them to make code reading easier
    */

	public function check_upload_diagram()
	{
		if( isset($this->request->files['diagram']) &&
		    !empty($this->request->files['diagram']) &&
		    $this->request->files['diagram']['name']
		){
			// upload file
			$r = $this->upload_file($this->request->files['diagram']);

			// return false if error
			if($r['error']) {
				$this->log->write('Category diagram upload error: ' . $r['error']);
				return false;
			}

            /*
               I added the two lines below.  The rest of this method and the upload_file() method were written by
               another developer.

             */
			// save model numbers from svg files to the database.
			$this->extractModelNumbers($this->request->files['diagram']);


			// set diagram value
			$this->request->post['diagram'] = $r['result'];
		}
		
		// return
		return true;
	}

    /**
     * @param array $file
     * @return bool
     */
    private function extractModelNumbers($file)
    {
        // If this is not an svg file then no need to proceed.
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        if($extension != 'svg' || $file['type'] != 'image/svg+xml') {
            return false;
        }

        // Get the SVGReader class.  Because it is outside of OpenCart's root dir, it will not get autoloaded by the
        // OpenCart autoloader.
        define('OUTER_ROOT_DIR', dirname(dirname(DIR_ADMIN))."/");
        include_once OUTER_ROOT_DIR.'autoloader.php';
        $mysqli    = $this->db->getMySQLi();// gets the database connection (without the OC Wrapper class).
        $svgReader = new SVGReader($mysqli);
        $path      = DIR_UPLOAD.$file['name'];

        // Gets the file id in DB, or inserts new entry if needed.
        $fileId = $svgReader->getFileId($file['name'], $path);

        $models = $svgReader->readFile($path);
        if(!empty($models)) {
            // SAVE MODEL NUMBERS TO THE DATABASE
            // First we have to convert the Model to an OpenCart product id.
            $products = $svgReader->getProductIds($fileId, $models);
            $added    = $svgReader->saveProducts($products);
        }
    }
}