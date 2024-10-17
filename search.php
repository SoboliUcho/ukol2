<?php
// get POST data
$response_arr = array();
if (!isset($_POST['search'])) {
    $response['status'] = 'error';
    $response['message'] = 'Není vyplněno pole search';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
$search = $_POST['search'];

// if (!isset($_GET['search']) || empty($_GET['search'])) {
//     $response_arr['status'] = 'error';
//     $response_arr['message'] = 'Není vyplněno pole search';
//     echo json_encode($response_arr, JSON_UNESCAPED_UNICODE);
//     exit();
// }
// $search = $_GET['search'];

// get data from google
$response = new Response($search);

// class Response for getting data from google
class Response
{
    public $status;
    public $message;
    public $data = array();

    private $file_manager;

    public function __construct($search)
    {
        $response = $this->get_data($search);
        if ($response === false) {
            return;
        }
        $this->status = 'success';
        $this->message = 'Data byla úspěšně načtena';
        $this->parse_data($response);
        // echo $response;
        $this->file_manager = new file_manager($this);
        $this->make_return();
    }

    // scraping data from google
    private function get_data($search)
    {
        $url = "https://www.google.com/search?q=" . urlencode($search);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=UTF-8'
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            $this->status = 'error';
            $this->message = 'Chyba při načítání dat: ' . curl_error($ch);
            return false;
        }

        // convert encoding to UTF-8
        $encoding = mb_detect_encoding($response, 'UTF-8, ISO-8859-1', true);

        if ($encoding !== 'UTF-8') {
            $response = mb_convert_encoding($response, 'UTF-8', $encoding);
        }
        // echo $response;
        return $response;
    }

    // parsing data from google
    private function parse_data($response)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($response);
        $xpath = new DOMXPath($dom);
        $main = $xpath->query('//div[@id="main"]')->item(0);
        $divs = $main->getElementsByTagName('div');
        $counter = 0;
        // get divs with search results
        foreach ($divs as $div) {
            if ($div->parentNode === $main) {
                // skip first div
                if ($counter == 0) {
                    $counter++;
                    continue;
                }
                // create new Search_Result object
                $result = new Search_Result($div);
                if ($result->valid) {
                    $this->data[] = $result;
                }
                $counter++;
            }
        }
    }
    public function data()
    {
        return $this->data;
    }
    public function make_return()
    {
        $response = array();
        $response['status'] = $this->status;
        $response['message'] = $this->message;
        $response['json'] = $this->file_manager->json();
        $response['xml'] = $this->file_manager->xml();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}

// class Search_Result for search results
class Search_Result
{

    public $valid = false;
    public $title;
    public $description;
    public $url;

    public function __construct($div)
    {
        $this->parse($div);
    }

    // parsing data from div and set title, description and url
    private function parse($div)
    {
        $semidivs = $div->getElementsByTagName('div');
        if ($semidivs->length == 0) {
            $this->valid = false;
            return false;
        }
        foreach ($semidivs as $semidiv) {
            $class = $semidiv->getAttribute('class');

            // get title
            if ($class == "BNeawe vvjwJb AP7Wnd") {
                $this->title = $semidiv->nodeValue;
                // echo $this->title;
                // echo "<br>";
            }
            // get description
            if ($class == "BNeawe s3v9rd AP7Wnd") {
                if ($semidiv->getElementsByTagName('div')->length > 0) {
                    continue;
                }
                $this->description = $semidiv->nodeValue;
                // echo $this->description;
                // echo "<br>";
            }
            // get url and remove unnecessary parts
            if ($class == "egMi0 kCrYT") {
                $a = $semidiv->getElementsByTagName('a')->item(0);
                $this->url = str_replace('/url?q=', '', $a->getAttribute('href'));
                $this->url = preg_replace('/&sa=.*/', '', $this->url);
                // echo $this->url;
                // echo "<br>";
            }
            if ($this->validate()) {
                return true;
            }
        }
        if ($this->validate()) {
            return true;
        }
        return false;
    }

    // validate div if it contains title, description and url
    private function validate()
    {
        if ($this->title && $this->description && $this->url) {
            $this->valid = true;
            return true;
        }
        return false;
    }
}

// class XML_generator for generating XML
class XML_generator
{
    private $response;
    private $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    public function __construct($response)
    {
        $this->response = $response;
        $this->generate();
    }
    // generate XML from response object
    private function generate()
    {
        $this->xml .= '<response>';
        $this->xml .= '<status>' . $this->response->status . '</status>';
        $this->xml .= '<message>' . $this->response->message . '</message>';
        if ($this->response->status == 'error') {
            $this->xml .= '</response>';
            return;
        }
        $this->xml .= '<data>';
        foreach ($this->response->data() as $result) {
            $this->xml .= '<result>';
            $this->xml .= '<title>' . $result->title . '</title>';
            $this->xml .= '<description>' . $result->description . '</description>';
            $this->xml .= '<url>' . $result->url . '</url>';
            $this->xml .= '</result>';
        }
        $this->xml .= '</data>';
        $this->xml .= '</response>';
    }
    public function xml()
    {
        return $this->xml;
    }
}

// class JSON_generator for generating JSON
class JSON_generator
{
    private $response;
    private $json;
    public function __construct($response)
    {
        $this->response = $response;
        $this->generate();
    }
    // generate JSON from response object
    private function generate()
    {
        $array = array();
        $array['status'] = $this->response->status;
        $array['message'] = $this->response->message;
        if ($this->response->status == 'error') {
            $this->json = json_encode($array, JSON_UNESCAPED_UNICODE);
            return;
        }
        $array['data'] = array();
        foreach ($this->response->data() as $result) {
            $array['data'][] = array(
                'title' => $result->title,
                'description' => $result->description,
                'url' => $result->url
            );
        }
        $this->json = json_encode($array, JSON_UNESCAPED_UNICODE);
    }
    public function json()
    {
        return $this->json;
    }
}

// class file_manager for saving JSON and XML
class file_manager
{
    private $response;
    public $json;
    public $xml;
    public function __construct($response)
    {
        $this->response = $response;
        $this->clean();
        $this->save();
    }
    // clean old files
    private function clean()
    {
        $files = scandir("search/");
        $files = array_diff($files, array('.', '..'));
        foreach ($files as $file) {
            // echo $file;
            if (filemtime("search/" . $file) < time() - 3600) {
                unlink("search/" . $file);
            }
        }
    }
    // save JSON and XML files
    private function save()
    {
        $time = strtotime(date('his'));
        $xml = new XML_generator($this->response);
        $json = new JSON_generator($this->response);
        file_put_contents('search/response' . $time . '.xml', $xml->xml());
        $this->xml = 'search/response' . $time . '.xml';
        file_put_contents('search/response' . $time . '.json', $json->json());
        $this->json = 'search/response' . $time . '.json';
    }
    public function json()
    {
        return $this->json;
    }
    public function xml()
    {
        return $this->xml;
    }
}
?>