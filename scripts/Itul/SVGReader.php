<?php
namespace scripts\Itul;

class SVGReader
{
    const IMAGE_DIR = "/parts-catalog/system/upload/";

    protected $db;

    public function __construct(\mysqli $db)
    {
        // NOTE: this is NOT the OpenCart class, it is a basic php mysqli.
        $this->db = $db;

        if(!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(dirname(__DIR__))."/");//    /home/nuov2ituldev/public_html/
        }
    }

    /**
     * Searches the IMAGE_DIR and pulls model #s out of svg files and saves them to the Database.
     *
     * This method is for cron jobs and command line.
     */
    public function run()
    {
        $dir   = new \DirectoryIterator(ROOT_DIR.self::IMAGE_DIR);
        $total = $this->getFileCount($dir);
        $i     = 0;

        echo "\n+----------------------+\n";
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {

                $fileName  = $fileinfo->getFilename();
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $path      = $fileinfo->getPathname();

                if($extension != 'svg') {
                    continue;
                }

                $i++;

                echo "Reading file $i of $total: ".$fileName;
                echo "\n";

                // GET THE DB FILE ID FROM TABLE itul_svg
                $fileId = $this->getFileId($fileName, $path);
                if($fileId === false) {
                    echo "Error getting file id.\n";
                    continue;
                }

                $models = $this->readFile($path);
                if(!empty($models)) {
                    // SAVE MODEL NUMBERS TO THE DATABASE
                    // First we have to convert the Model to an OpenCart product id.
                    $products = $this->getProductIds($fileId, $models);// $products[$fileId][product_id] = $model;
                    $added    = $this->saveProducts($products);
                    if($added > 0) {
                        echo "    added $added entries to the database.\n\n";
                    }
                }
            }
        }
        echo "+--------  END OF SCRIPT   --------------+\n";
    }

    /**
     * @param string $path -- includes the filename and file extension also.
     * @return array
     */
    public function readFile($path)
    {
        $models = array();
        $file   = fopen($path, 'r');

        if (is_resource($file) === true)
        {
            while (feof($file) === false) {
                $line = fgets($file);

                if(stripos($line, "text transform") !== false) {
                    $beforeNeedle = strstr($line, '</', true);
                    $models[]     = ltrim( strstr($beforeNeedle, '>'), '>');
                }
            }
            fclose($file);
        }
        return $models;
    }

    /**
     * @param string $fileName
     * @param string $path
     * @return bool|int
     */
    public function getFileId($fileName, $path)
    {
        // IS THE DIAGRAM ALREADY IN THE DATABASE?
        $fileId = $this->isInDatabase($fileName);

        if($fileId === false) {
            $fileId = $this->addFileToDatabase($fileName, $path);
        }
        return $fileId;
    }

    /**
     * @param int   $fileId
     * @param array $models
     * @return array
     */
    public function getProductIds($fileId, $models)
    {
        $products = array();
        foreach ($models as $model) {
            $sql = "SELECT product_id FROM oc_product WHERE model = '$model'";
            $query = $this->db->query($sql);

            if($query->num_rows > 0) {
                $result = $query->fetch_object();
                $products[$fileId][$result->product_id] = $model;
            }
        }
        return $products;
    }

    /**
     * @param array $products
     * @return int
     */
    public function saveProducts($products)
    {
        // $products[svg_id][product_id] = model;
        $added = 0;
        foreach ($products as $svgId => $productIdArray) {
            foreach ($productIdArray as $productId => $model) {
                if($this->hasPartsEntry($svgId, $productId) === false) {
                    // INSERT PRODUCT IN DB.
                    $svgId     = intval($svgId);
                    $productId = intval($productId);

                    $sql = "INSERT INTO itul_svg_parts
                            (`svg_id`, `product_id`, `model`)
                            VALUES
                            ($svgId, $productId, '$model')";
                    $query = $this->db->query($sql);

                    if($query) {
                        $added++;
                    }
                }
            }
        }
        return $added;
    }

    /**
     * @param string $fileName
     * @return bool|int
     */
    private function isInDatabase($fileName)
    {
        $sql = "SELECT * FROM itul_svg WHERE name = '$fileName'";
        $query = $this->db->query($sql);

        if($query->num_rows > 0) {
            // return the Id.
            $result = $query->fetch_object();
            return $result->id;
        }
        return false;
    }

    /**
     * @param string $fileName
     * @param string $path
     * @return bool|int
     */
    private function addFileToDatabase($fileName, $path)
    {
        $sql = "INSERT INTO itul_svg
                (`name`, `path`)
                VALUES
                ('$fileName','$path')";
        $query = $this->db->query($sql);

        if($query) {
            // get last insert id.
            $id = $this->db->insert_id;
            return $id;
        }
        return false;
    }

    /**
     * @param int $svgId
     * @param int $productId
     * @return array|bool
     */
    private function hasPartsEntry($svgId, $productId)
    {
        return $this->getRow("
            SELECT
            *
            FROM
            itul_svg_parts
            WHERE
            svg_id = $svgId AND product_id = $productId
        ");
    }

    /**
     * @param \DirectoryIterator $dir
     * @return int
     */
    private function getFileCount($dir)
    {
        $count = 0;
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {

                $extension = pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION);

                if ($extension == 'svg') {
                    $count++;
                }
            }
        }
        return $count;
    }

    //----------------------------------------------------------------------------------//
    //-------         DATABASE HELPERS                      ----------------------------//
    //----------------------------------------------------------------------------------//

    /**
     * GET MULTIPLE ROWS FROM THE DATABASE
     *
     * @param $query
     * @return array|bool
     */
    private function getRows($query)
    {
        $return = array();
        $result = $this->db->query($query);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $return[] = $this->arrayToObject($row);
            }
        }
        if(!empty($return)){
            return $return;
        }
        return false;
    }

    /**
     * GET A ROW FROM THE DATABASE
     *
     * @param $query
     * @return array|bool
     */
    private function getRow($query)
    {
        $result = $this->db->query($query);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                return $row;
            }
        }
        return false;
    }

    /**
     * CONVERT AN ARRAY TO AN OBJECT
     *
     * @param $data
     * @return \stdClass
     */
    private function arrayToObject($data)
    {
        if(is_array($data)){
            $obj = new \stdClass;
            foreach($data as $key=>$elm){
                if(is_array($elm)){
                    $elm = $this->arrayToObject($elm);
                }
                $obj->$key = $elm;
            }
            return $obj;
        }
    }
}