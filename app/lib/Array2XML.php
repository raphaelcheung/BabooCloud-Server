<?php

namespace app\lib;

class Array2Xml
{
    private $version  = '1.0';
    private $encoding  = 'UTF-8';
    private $root    = 'root';
    private $xml    = null;
    function __construct()
    {
    }
    function saveXml($data, $filename)
    {
        $this->xml = xmlwriter_open_uri($filename);

        //设置缩进
        xmlwriter_set_indent_string($this->xml, '    ');
        xmlwriter_set_indent($this->xml, 1);
        xmlwriter_start_document($this->xml, $this->version, $this->encoding);
        xmlwriter_start_element($this->xml, $this->root);


        foreach($data as $key => $value)
        {
            if(is_array($value))
            {
                xmlwriter_start_element($this->xml, $key);
                $this->_toXml($value);
                xmlwriter_end_element($this->xml);
                continue;
            }
            xmlwriter_write_element($this->xml, is_string($key) ? $key : sprintf('%d', $key), $value);
        }

        xmlwriter_end_element($this->xml);
        xmlwriter_end_document($this->xml);
    }

    private function _toXml($data)
    {
        foreach($data as $key => $value)
        {
            if(is_array($value))
            {
                xmlwriter_start_element($this->xml, $key);
                $this->_toXml($value);
                xmlwriter_end_element($this->xml);
                continue;
            }

            if (is_string($key)){
                xmlwriter_write_element($this->xml, $key, $value);
            }else{
                $tmp = sprintf('%d', $key);
                xmlwriter_write_element($this->xml, $tmp, $value);
            }
            
        }
    }
}
