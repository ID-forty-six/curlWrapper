<?php

namespace idfortysix\curlwrapper;
 
define("DATETIME_FORMAT",       'Y-m-d H:i:s');
define("DAY_SEC",                       60*60*24);
define("TLDS",
        "academy|accountants?|apartments|aero|agency|app|archi|associates|audio|bar|auction|best|bike|black|blog|business|cab|xyz|cards|career|cheap|club|coffee|date|design|directory|download|education|fly|gift|global|men|mov|ninja|photos".
        "|eu|net|com|org|net|biz|info|io|fm|mobi|mobile|museum|gov|name|tv|pro|co|cc|email|edu|tools|guru|london".
        "|lt|pl|lv|ru|ee|fi|it|by|de|fr|cz|sk|hu|hr|no|me|se|es|uk|dk|nl|ch|be|ua|tr|ge|ie|at|pt|gr|ro|lu|bg|ba|rs|al|mk|am|li|il|si|kz|gb".
        "|us|ca|as|in|hk|au|ar|is|jp|cn|ae|sg|nz|vn|ir|pk|kr"
        );
date_default_timezone_set('Europe/Vilnius');
 
/**
 * ParserBase - configai ir bazines funkcijos
 *
 * @author tautvydas
 */
abstract class ParserBase {
       
        protected $page;
       
        /*
         * salis - skirta telefonu parsinimui
         */
        protected $country;
        protected $country_code;
       
        protected $city_code    = '';           // Miesto kodas telefonui
        protected $drop_phones  = false;        // ar pasalinti telefonus, kuriu ilgis blogas
       
        protected $skip_links = "jpe?g|png|tiff?|bmp|xcf|gif|docx?|pdf|xlsx?|css|xml|ico|mp3|wav|mpg|avi|swf|iso|zip|js|exe|gz";
       
        /**
         * patternai skirti atpazinti tam tikrus HTML teksto darinius
         * @var type
         */
        protected $patterns;
       
        protected $spamtraps;
       
        /**
         * ar padaryti stringus mazosiomis raidemis
         * @var type
         */
        protected $make_strlower = false;
       
        public function __construct()
        {
                $this->patterns = [
                        'SPACE'         => "(\s*)",
                        'NOTAG'         => "([^\<\>]*?)",
                        'WORDS'         => "([\s\w\p{L}\p{M}\-\.\,\;\:\(\)\!\?]*?)",
                        'PHONE'         => "((\+\d{1,3})?([\s\(\-]*\d+[\s\)\-]*)+)",
                        'PHONEBR'       => "((\+\d{1,3})?\(\d+\s*\d*\)([\s\-]*\d+[\s\-]*)+)",
                        'LETTER'        => "([a-z\p{L}\p{M}]*?)",
                        'ALFANUM'       => "([\w\p{L}\p{M}]*?)",
                        'EMAILUTF'      => "\b([\w\p{L}\p{M}_\-]+(\.[\w\p{L}\p{M}_\-]+)*@([\w\p{L}\p{M}\-]+\.)*[\w\p{L}\p{M}\-]{2,}\.(".TLDS."))\b",
                        'EMAIL'         => "\b([\w_\-]+(\.[\w_\-]+)*@([\w\-]+\.)*[\w\-]{2,}\.(".TLDS."))\b",
                        'STR'           => "(.*?)",
                        'STRWQ'         => "([^\"]+)",
                        'STR1'          => "(.?)",
                        'URL'           => "([\w\s\p{L}\p{M}\/\&\?\%\=\.\,\;\:\-\(\)\'\+\~]+?)",
                        'DOMAIN'        => "\b(([\w\-]+\.)*[a-z0-9\-]+\.(".TLDS."))\b",
                        'NR'            => "(\d+)",
                        'LT_MOBILE'     => "((\+370|8)[\s\-\(]{0,3}6[\d\s\-]{2,5}\)?[\s\-]{0,2}[\d\s]{5,8})",
                        'PL_MOBILE'     => "((\+48)?[\s\-\(]{0,3}(50|51|53|57|60|66|69|72|73|78|79|88)[\s\-\)]{0,3}((\d{1}[\s\-]{0,2}\d{3}[\s\-]{0,2}\d{3})|(\d{3}[\s\-]{0,2}\d{2}[\s\-]{0,2}\d{2})))",
                        'UK_MOBILE'     => "((\+44)?[\s\-]{0,2}(\(0\))?0?[\s\-\(]{0,2}7\d{2,5}\)?[\s\-]{0,2}\d{3,6}([\s\-]{0,2}\d{3,6})?)"
                ];
                $this->spamtraps = [
                        // spam, bet idedamos islygos spamedic, spamarin, spamaster, spamanager, spampering
                        "spam(?!edic|aster|anager|pering|arketing)",
                        "spamcatcher",
                        "spamarrest",
                        "spamtrap",
                        "spamreport",
                        "dummy",
                        "whatever",
                        "sucker",
                        "unsolicited",
                        "stopsending",
                        "booby@trap",
                        "nowhere",
                        "spoof",
                        "abuse",
                        "harvested",
                        "joe@aol",
                        "scum@",
                        "mailcatch",
                        "remailled",
                        "discard",
                        "sourcemail\.org",
                        "catch@",
                        "junk@",
                        "junkmail",
                        "johndoe",
                        "domain\.co",
                        "example",
                        "email@company",
                        "name@email",
                        "@email\.",
                        "username",
                        "emailaddress",
                        "webmaster",
                        "postmaster",
                        "donate",
                        "privacy@",
                        "demolink\.",
                        "app\.getsentry",
                        "you@yoursite",
                        "opencart\.",
                        "yourdomain",
                        "yoursite",
                        "yourname",
                        "your@",
                        "your\.",
                        "you@address",
                        "license@",
                        "prestashop\.",
                        "paypal",
                        "ebay\.",
                        "phishing",
                        "no\-?reply",
                        "^report@",
                        "admin@",
                        "emailprovider",
                        "webadmin|hostmaster|domen|visalietuva|sample|pastas|someurl|userinput|pavyzdys|vardas|pavarde|vardenis|pavadinimas|przyklad|adres",
                        "\d{4,}",                                       // su skaiciu deriniais - itartini emailai kur yra is eiles pasikartojantys skaiciai
                        "\d+_?\-?[a-z]+_?\-?\d+"        // skaiciai Raides Skaiciai - itartinas, pasalinam
                ];
        }
 
        /**
         * pasalinam newlainus ir triminam
         */
        protected function rmNewlinesWhitesp($str) {
                $str = preg_replace("/^\s+|\s+$/imu", "", $str);
                $str = str_replace(["\n", "\r"], "", $str);
                return $str;
        }
       
        protected function getPattern($pattern_line) {
                $str = preg_quote( $this->rmNewlinesWhitesp($pattern_line) );
                $str = "/".str_replace("/", "\/", $str)."/isu";
 
                $search = $replace = array();
                foreach ($this->patterns as $p=>$r) {
                        $search[] = "\{$p\}";
                        $replace[] = $r;
                }
                return str_replace($search, $replace, $str);
        }
       
        protected function setCountryCode($country){
                switch($country){
                        case "LT": $this->country_code = "+370"; break;
                        case "PL": $this->country_code = "+48"; break;
                        case "UK": $this->country_code = "+44"; break;
                }
                $this->country = $country;
                return $this;
        }
 
        protected function isPhoneMobile($phone) {
                switch ($this->country) {
                        case "LT":
                                return (strpos($phone, "+3706") === 0);
                        case "UK":
                                return (strpos($phone, "+447") === 0 && strlen($phone) == 13);
                        case "PL":
                                return preg_match("/^\+48(50|51|53|57|60|66|69|72|73|78|79|88)/iu", $phone);
                }
        }
 
        protected function formatPhoneLT($phone) {
                // Lietuva
                if (strlen($phone) == 11 && strpos($phone, "370") === 0)
                {
                        $phone = "+".$phone;
                }
                elseif (strlen($phone) == 9 && strpos($phone, "8") === 0)
                {
                        // pirma elementa keiciame i +370
                        $phone = $this->country_code . substr($phone, 1);
                }
                elseif (strlen($phone) == 8)
                {
                        $phone = $this->country_code . $phone;
                }
                elseif (strlen($this->city_code . $phone) == 8)
                {
                        $phone = $this->country_code . $this->city_code . $phone;
                }
                elseif ($this->drop_phones)
                {
                        $phone = '';
                }
                return $phone;
        }
       
        protected function formatPhonePL($phone) {
                // Lenkija
                // skaiciu be salies kodo -> 9
                if (strlen($phone) == 11 && strpos($phone, "48") === 0)
                {
                        $phone = "+".$phone;
                }
                elseif (strlen($phone) == 9)
                {
                        $phone = $this->country_code . $phone;
                }
                elseif (strlen($this->city_code . $phone) == 9)
                {
                        $phone = $this->country_code . $this->city_code . $phone;
                }
                elseif ($this->drop_phones)
                {
                        $phone = '';
                }
                return $phone;
        }
       
        protected function formatPhoneUK($phone) {
                // anglija : telefono numeris gali buti 9 ar 10 skaitmenu + salies kodas
                // Mobilus numeriai : tik +44 ir 10 skaitmenu
                if ((strlen($phone) == 12 || strlen($phone) == 13) && strpos($phone, "440") === 0)
                {
                        $phone = $this->country_code . substr($phone, 3);
                }
                elseif ((strlen($phone) == 11 || strlen($phone) == 12) && strpos($phone, "44") === 0)
                {
                        $phone = "+".$phone;
                }
                elseif ((strlen($phone) == 10 || strlen($phone) == 11) && strpos($phone, "0") === 0)
                {
                        $phone = $this->country_code . substr($phone, 1);
                }
                elseif ((strlen($phone) == 9 || strlen($phone) == 10) && strpos($phone, "0") !== 0)
                {
                        $phone = $this->country_code . $phone;
                }
                elseif ($this->drop_phones) {
                        // visais kitais atvejais naikiname telefona, nes neatitinka tel. nr. ilgis
                        $phone = '';
                }
                return $phone;
        }
       
       
       
        /**
         * Gauname domena ir subdomena ish URL
         * @param type $url
         * @return type
         */
        public function getDomain($url) {
                $subdomain = $this->getSubdomain($url);
                if (preg_match("/\b[\w\-]+(\.com?)?\.[a-z]{2,12}$/i", $subdomain, $match)) {
                        return $match[0];
                }
        }
 
        public function getSubdomain($url) {
                if (preg_match('/'.$this->patterns['DOMAIN'].'/i', $url, $match)) {
                        return $match[0];
                }
        }
 
        /**
         * email funkcija - pravalome nekorektiskus emailus / spamtrapus
         * t.p. pataisome .co.uk.co.uk
         */
        public function cleanupEmails(array $emails)
        {
                foreach ($emails as $key => &$item)
                {
                        foreach($this->spamtraps as $spamtrap)
                        {
                                if (preg_match("/$spamtrap/iu", $item))
                                {
                                        unset($emails[$key]);
                                        break;
                                }
                        }
                        if (array_key_exists($key, $emails) && preg_match("/\.co\.uk\.co\.uk$/iu", $item))
                        {
                                $item = preg_replace("/\.co\.uk\.co\.uk$/iu", ".co.uk", $item);
                        }
                }
                return $emails;
        }
 
        public function make_str_lower($is_lower=true)
        {
                $this->make_strlower= $is_lower;
                return $this;
        }
       
        public function arr_to_str(array $arr, $implode_str=", ") {
                return implode($implode_str, array_unique(array_filter($arr)));
        }
 
        public function str_to_array($str, $delimiter=', ')
        {
                if ($this->make_strlower)
                {
                        $str = mb_strtolower($str);
                }
                $arr = explode(trim($delimiter), $str);
                foreach($arr as &$item)
                {
                        $item = trim($item);
                }
                return array_unique(array_filter($arr));
        }
 
        /*
         * kableliais atskirti elementai - sukergiam ir padarome unikalius
         */
        public function get_str_uniq($str, $delimiter=', ') {
                return $this->arr_to_str( $this->str_to_array($str, $delimiter), $delimiter);
        }
 
}
