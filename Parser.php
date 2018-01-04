<?php

namespace idfortysix\curlwrapper;
 
/**
 * Description of Parser
 *
 * @author tautvydas
 */
class Parser extends ParserBase {
       
        public function __construct($page, $country="UK", array $extra_patterns=[]) {
                parent::__construct();
                mb_internal_encoding("UTF-8");
                $this->setPage($page);
                $this->patterns = $this->patterns + $extra_patterns;
                $this->setCountryCode($country);
        }
 
        /**
         * pagrindine parsinimo funkcija
         *
         * @param type $pattern_line
         * @return null
         */
        public function getStrings($pattern_line) {
                $pattern = $this->getPattern($pattern_line);
                if (preg_match_all($pattern, $this->page, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as &$match_arr) {
                                foreach ($match_arr as &$item) {
                                        $item = trim($item);
                                }
                        }
                        return $matches;
                }
                return null;
        }
       
        public function getPage() {
                return $this->page;
        }
 
        public function setPage($page) {
                $this->page = $this->rmNewlinesWhitesp($page);
        }
 
       
        public function setPhoneCityCode($code)
        {
                $this->city_code = $code;
                return $this;
        }
       
        public function dropBadPhones($do_drop=true)
        {
                $this->drop_phones = $do_drop;
                return $this;
        }
       
        /**
         * Telefonu formatavimas
         * @param type $phones
         * @param type $split_mobiles
         * @return string
         */
        public function formatPhones($phones, $split_mobiles=false) {
                $mobiles = $landlines = [];
                if (!$phones) {
                        return $split_mobiles ? [] : '';
                }
                if (is_string($phones)) {
                        $phones = explode(",", $phones);
                }
                // TODO: kartais tel. nr. nurodytas be pliuso priekyje - reikia suvienodinti
                // TODO: jei telefonas neatitinka skaiciu kiekio ji ismetame
               
                foreach ($phones as &$phone)
                {
                        // pasaliname visus nereikalingus simbolius, taip pat "+"
                        $phone = preg_replace("/[^\d]/iu", "", $phone);
                        if ($this->country === "LT")
                        {
                                $phone = $this->formatPhoneLT($phone);
                        }
                        elseif ($this->country === "UK")
                        {
                                $phone = $this->formatPhoneUK($phone);
                        }
                        elseif ($this->country === "PL")
                        {
                                $phone = $this->formatPhonePL($phone);
                        }
                        if ($split_mobiles)
                        {
                                // jei mobilus tel:
                                if ($this->isPhoneMobile($phone))
                                {
                                        $mobiles[] = $phone;
                                }
                                else
                                {
                                        $landlines[] = $phone;
                                }
                        }
                }
                if ($split_mobiles)
                {
                        $ret_landlines  = $this->arr_to_str($landlines);
                        $ret_mobiles    = $this->arr_to_str($mobiles);
                        return array(
                                ($ret_landlines ? $ret_landlines : null),
                                ($ret_mobiles   ? $ret_mobiles : null)
                        );
                }
                else {
                        $ret = $this->arr_to_str($phones);
                        return $ret ? $ret : null;
                }
        }
       
        /**
         * gaunam linkus is duoto WWW puslapio
         * perduodame URL parametra, kad linkus butu galima paversti ish relative i absolute
         * $base_url pareina su http://
         */
        public function getLinks($base_url)
        {
                $base_url = rtrim($base_url, "/");              // numetam trailing slash
                $links = $res = array();
 
                if (preg_match_all("/\<a[^\<\>]+?href=[\'\"]([^\<\>]+?)[\'\"]/iu", $this->page, $matches)) {
                        $links = $matches[1];
                }
                if (preg_match_all("/\<a[^\<\>]+?href=([^\'\"][^\<\>]+?[^\'\"])[\s\>]/iu", $this->page, $matches)) {
                        $links = array_merge($links, $matches[1]);
                }
 
                $links = array_unique($links);
 
                foreach($links as $key => &$val)
                {
                        // 1. pasalinam neatitinkancius formato linkus
                        if (
                                !$val
                                ||      preg_match("/^\w+\:/iu", $val) && !preg_match("/^https?\:\/\//iu", $val)        // jei schema protocol:// ir nera http://
                                ||  preg_match("/^\/?&?#.*/iu",                                         $val)   // jei ancor linkas
                                ||  preg_match("/^https?:\/\/\s*$/iu",                          $val)   // atmetam jei tuscias linkas
                                ||  preg_match("/\.(".$this->skip_links.")$/iu",        $val)   // jei linkas i paveiksliuka ar panasiai
                        ) {
                                unset($links[$key]);
                                continue;
                        }
 
                        // 2. modifikuojame - relative ar absolute link
                        if (preg_match("/^\//iu", $val))
                        {
                                $val = "http://".$this->getSubdomain($base_url) . $val;
                        }
                        elseif (!preg_match("/^http/iu", $val))
                        {
                                $val = $base_url."/".$val;
                        }
                }
 
                $links = array_unique($links);
 
                foreach ($links as $url) {
                        $res[] = array('url' => $url, 'domain' => $this->getDomain($url), 'subdomain' => $this->getSubdomain($url));
                }
                return $res;
        }
       
        /**
         * mb_ucfirst emuliacija
         * @param type $str
         */
        public function makeFirstUpper($str)
        {
                return mb_strtoupper(mb_substr($str, 0, 1), 'UTF-8').mb_substr($str, 1);
        }
       
        /**
         * generuoja SEO linka is paprasto string'o
         * @param type $str
         * @param type $leave_utf_chars
         * @return type
         */
        public function generateSeoString($str, $leave_utf_chars=false) {
                if ($leave_utf_chars)
                {
                        $dashed = preg_replace("/[^\w\p{L}\p{M}]+/iu", '-', $str);
                }
                else
                {
                        $f_n = str_replace(
                                array('à','á','â','ã','ä','å','æ', 'ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ','ā','đ','ġ','ă','ē','ģ','ą','ĕ','ĥ','ć','ė','ħ','ĉ','ę','ĩ','ċ','ě','ī','č','ĝ','ĭ','ď','ğ','į','ı','ĳ', 'ĵ','ķ','ĸ','ĺ','ļ','ľ','ŀ','ł','ń','ņ','ň','ŉ','ŋ','ō','ŏ','ő','š','ű','œ', 'ţ','ų','ŕ','ť','ŵ','ŗ','ŧ','ŷ','ř','ũ','ź','ś','ū','ż','ŝ','ŭ','ž','ş','ů','ſ','α','β','γ','δ','ε','ζ','η','θ', 'ι','κ','λ','μ','ν','ξ','ο','π','ρ','ς','σ','τ','υ','φ', 'χ', 'ψ', 'ά','έ','ή','ί','ΰ','ω','ϊ','ϋ','ό','ύ','ώ','а','б','в','г','д','е','ж','з','и','й', 'к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч', 'ш', 'щ', 'ъ','ы','ь','э','ю','я','ё','ђ','ѓ','є','ѕ','і','ї','ј','љ','њ','ћ','ќ','ў','џ'),
                                array('a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','h','n','o','o','o','o','o','o','u','u','u','u','y','y','a','d','g','a','e','g','a','e','h','c','e','h','c','e','i','c','e','i','c','g','i','d','g','i','i','ij','j','k','k','i','i','l','l','l','n','n','n','n','n','o','o','o','s','u','ae','t','u','r','t','w','r','t','y','r','u','z','s','u','z','s','u','z','s','u','f','a','b','g','d','e','z','h','th','i','k','l','m','n','x','o','p','r','s','s','t','u','ph','ch','ps','a','e','i','i','u','o','i','u','o','u','o','a','b','v','g','d','e','z','z','i','ij','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','ch','b','i','', 'e','u','a','o','h','g','e','s','i','i','j','l','l','h','k','u','c'),
                                mb_strtolower($str));
                        $dashed = preg_replace("/(\W+)/iu", '-', $f_n);
                }
                // pasalinam pirma ir paskutini bruksniukus
                return preg_replace("/(^\-)|(\-$)/iu", '', $dashed);
        }
 
 
}
