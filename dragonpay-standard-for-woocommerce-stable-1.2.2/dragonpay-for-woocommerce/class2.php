<?php
/**
*  class Xml2Csv
* 
* 
*  This class is an easy to use, highly customisable XML to CSV converter. You can automatically convert XML to CSV, with only 1 parameter - the XML input (file or string).
*  Advanved features include custom selection of the output fields, setting CSV delimiter and enclosure character etc.
*  
*  Features:
* 
*    - easy conversion with only 1 parameter through static function call 
*    - file or string as XML input
*    - CSV delimiter and enclosure character setting
*    - custom mapping of XML nodes to CSV fields
*    - custom export field selection
*    - setting parameters easily by chained function calls or by accessing them directly
*    - possibilty to mass-setup of paramters
*    - set an interval to limit the number of processed items (from, to)      
* 
* 
*  Examples: 
*  <code>
* 
*    // Example 1 - easy automatic conversion  
*    XmlToCsv::convert('example.xml');
* 
*    // Example 2 -  chained parameter setting
*    $x = new XmlToCsv();
*    echo $x->url('example.xml')
*           ->output(false)
*           ->autoConvert();
*
*    // Example 3 - parameters set separately by directly accessing the class variables
*    $x = new XmlToCsv();
*    $x->url = 'example.xml';
*    $x->output = false;
*    echo $x->autoConvert();
*
*    // Example 4 - parameters set at object creation as an array 
*    $x = new XmlToCsv(array(
*            'url'=>'example.xml',
*            'output'=>'echo',
*            'importTo'=>3,
*        ));
*    $x->autoConvert();
* 
* </code> 
*/
class XmlToCsv {

    /**
    * the URL to the XML file (local or remote). If $xml is filled, then $url is ignored
    * 
    * @var string
    */
    public $url = '';

    /**
    * the XML string, can be used to directly input the XML. If this is filled, the $url param is ignored 
    * 
    * @var string
    */
    public $xml = '';

    /**
    * the filename used for the newly created CSV, if $output is set to 'file' 
    * 
    * @var string
    */
    public $filename = "export.csv";

    /**
    * indicate FROM which entry to begin to export the data. This can be used if you want to export only some items from the XML, not all of them. 
    * Defaults to. If used together with $importTo, you can set an interval for selecting specific items from the XML
    * 
    * @var int
    */
    public $importFrom = 0;

    /**
    * indicate TO which entry to export the data. This can be used if you want to export only some items from the XML, not all of them
    * Defaults to 999999999. If used together with $importFrom, you can set an interval for selecting specific items from the XML
    * 
    * @var int
    */
    public $importTo = 99999999;

    /**
    * the character to use as CSV delimiter. Only 1 character.
    * 
    * @var string
    */
    public $delimiter = ",";

    /**
    * the character to use to enclose items in the CSV . Only 1 character.
    * 
    * @var string
    */
    public $enclosure = '"';

    /**
    * output type:
    *             - 'string' (default) - return as CSV string 
    *             - 'file' - return as CSV file 
    *             - 'echo' - write CSV out to the document
    *             - 'array' - return as an array prepared for further use
    * @var string
    */
    public $output = 'string';

    /**
    * set if the CSV header line should appear in the output
    * @var bool
    */
    public $header = true;

    /**
    * set if only the selected fields (defined in $map) should appear in the output. If FALSE - all fields will be put out
    * 
    * @var bool
    */
    public $selectedFields = false;

    /**
    * This key=>value paired array can be used to map XML nodes to CSV fields. By default all exported XML nodes are mapped to CSV fields with the same name.
    * Example: 
    * <code>
    *   <root>
    *       <item>
    *           <node1>Text1</node1>
    *           <node2>Text2</node2>
    *       </item>
    *   </root>
    * </code>
    * 
    * This XML would mapped to the CSV like this:
    * 
    *   <pre>
    *     node1,node2
    *     Text1,Text2
    *   </pre>
    * 
    * If you wand to map the nodes to different fields, you have to pass a mapping to $map:
    *   <code>
    *   $this->map = array(
    *                   'csv1' => 'node1',
    *                   'csv2' => 'node2',  
    *                )
    *   </code>  
    * 
    * This would then result in the follwing CSV export:
    *   <pre>
    *     csv1,csv2
    *     Text1,Text2
    *   </pre>
    * 
    * @var array
    */
    public $map = array();

    /**
    * XPath expression to determine the Item nodes (=CSV rows) for the export
    * Defaults to the 2nd child node in the XML
    * 
    * @var string
    */
    public $item = "/*/*";

    /**
    * @method $this->VARIABLE - this function enables to write to any class variable through the corresponging function name.
    * This way it is possible to chain-call and set all needed variables.
    * Example:
    * <code> 
    *     $x = new XmlToCsv();
    *     echo $x->url('a.xml')
    *            ->output(false)
    *            ->autoConvert();
    * </code>
    * 
    * @param mixed $name
    * @param mixed $arguments
    * @return XmlToCsv
    */
    public function __call($name, $arguments) {

        if(method_exists($this,$name)) {
            return $this->$name($arguments);    
        } else {

            if(!is_array($arguments) or $arguments==null) {
                $arguments[0] = null;
            }
            return $this->_setParam($name,$arguments[0]);
        }

    }

    /**
    * The class constructor.
    * 
    * @param array $params - if this paramaters is a filled array, set up all paramters (equivalent to $this->setParams())
    * @return XmlToCsv
    */
    public function __construct($params=null) {

        $this->clearParams();

        if(is_array($params)) {
            if(!empty($params)) {
                $this->setParams($params);
            }
        } 
        return $this;
    }

    /**
    * internal method used to set a parameter value
    * 
    * @param string $name
    * @param mixed $value
    * @return XmlToCsv
    */
    private function _setParam($name,$value) {

        $this->$name = $value;
        return $this;
    }

    /**
    * method used to set more paramters at once
    * 
    * @param array $params_array - array of paramName=>paramValue pairs
    * @return XmlToCsv
    */
    public function setParams($params_array) {

        foreach($params_array as $key=>$value) {
            $this->$key = $value;
        }
        return $this;
    }

    /**
    * This is the function which does the whole magic conversion.
    * Basically all that is needed is the XML source. With no other parameters, it automatically converts the XML to CSV.
    * All previously set up paramtere are used to extend the default functionality. 
    * 
    * @return mixed 
    *            - string - if $output is set to 'string' and the conversion is OK
    *            - bool - otherwise. True on success and false on failure   
    */
    public function autoConvert() 
    {
        $map = $this->map;
        $remap = array_flip($map);

        $doc = new DOMDocument;

        if($this->xml <> "") {


            $ok = $doc->loadXML($this->xml);

        } elseif($this->url <> "") {

            $ok = @$doc->load($this->url);
            // try to load doc via CURL if the prevoius load failed
            if($ok===false) {

                if(function_exists('curl_version')) {                
                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_URL, $this->url);

                    $ok = curl_exec($curl);
                    $ok = $doc->loadXML($ok);
                    curl_close($curl);
                } else {
                    return false;
                }

            }

        } else {

            return false;

        }
        if(!$ok) {
            return false;
        }

        $xpath = new DOMXPath($doc);
        $entries = $xpath->evaluate($this->item,$doc);
        $csv = array();
        $i = 1;

        foreach($entries as $el) {
            foreach($el->childNodes as $node) {

                if(!empty($map)) {

                    // not a real node OR not in the mapped array, and only mapped fields should be output
                    if($node->nodeType==3 or (!in_array($node->nodeName,$map) and $this->selectedFields)) continue;

                    if(!in_array($node->nodeName,$map)) {

                        $csv[0][$node->nodeName]=$node->nodeName;
                        $csv[$i][$node->nodeName] = $node->textContent;    

                    } else {

                        $csv[0][$node->nodeName]=$remap[$node->nodeName];
                        $csv[$i][$remap[$node->nodeName]] = $node->textContent;    

                    }
                } else {

                    if($node->nodeType==3) continue;
                    $csv[0][$node->nodeName]=$node->nodeName;
                    $csv[$i][$node->nodeName] = $node->textContent;    
                }
            }
            $i++;
        }

        if($this->output=='array') {
            return $csv;
        }

        ob_start();
        $fp = fopen("php://output", 'w');
        $i = 0;
        foreach ($csv as $fields) {
            $i++;
            if(($i <= $this->importFrom and $i<>1) or ($i==1 and !$this->header)) {
                continue;
            }

            if($i==1) {
                $c = $fields;  
            } else {
                $c = array();
                foreach($csv[0] as $f) {
                    $c[] = $fields[$f];
                }
            }
            fputcsv($fp, $c,$this->delimiter,$this->enclosure);

            if($i > $this->importTo) {
                break;
            }
        }
        fclose($fp);


        if($this->output=='file') {
            header("Content-Disposition: attachment; filename=\"".$this->filename."\";" );
            header("Content-Type: application/octet-stream");
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false);
            header("Content-Transfer-Encoding: binary"); 
        }

        if($this->output=='echo' or $this->output=='file') {
            echo ob_get_clean();
            return true;

        } else {
            return ob_get_clean();;
        }

    }
    /**
    * convert XML to CSV with a mapping setup - only the mapped fields will be exported. 
    * If $mapping is empty, the previously set parameters and mapping is used. If no mapping was set, the function returns the result as
    * $this->autoConvert()
    * 
    * @param mixed $mapping - array of csvField=>xmlNode pairs 
    * @see $map
    */
    public  function mapConvert($mapping=array()) 
    {
        return $this->map(array_merge($this->map,(array)$mapping))->autoConvert();
    }

    /**
    * automaticaly convert XML from the specified URL and return as CSV string
    * @param string $url
    */
    public static function convert($url) 
    {
        $x = new XmlToCsv();
        echo $x->url($url)->autoConvert();

    }

    /**
    * automaticaly convert XML from the specified XML string and return as CSV string
    * @param string $url
    */
    public static function convertString($xmlString) 
    {
        $x = new XmlToCsv();
        echo $x->xml($xmlString)->autoConvert();

    }
}
