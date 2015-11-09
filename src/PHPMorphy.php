<?php
namespace rock\morphy;


use rock\base\Alias;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;

class PHPMorphy implements ObjectInterface
{
    use ObjectTrait;

    /**
     * @var \phpMorphy
     */
    protected static $morphy;
    /**
     * Highlight template.
     * @var string
     */
    protected $highlightTpl = '<span class="highlight">$1</span>';
    /**
     * Path to dictionaries.
     * @var string
     */
    protected $pathDict;
    /**
     * Default locale.
     * @var string
     */
    protected $locale = 'en';


    public function init()
    {
        if (!isset(static::$morphy)) {
            if (!isset($this->pathDict)) {
                $this->pathDict = __DIR__ . '/dicts/';
            }
            try {

                $dictBundle = new \phpMorphy_FilesBundle($this->pathDict, $this->normalizeLocale());
                static::$morphy = new \phpMorphy(
                    $dictBundle,
                    [
                        'storage' => PHPMORPHY_STORAGE_FILE,
                        'with_gramtab' => false,
                        'predict_by_suffix' => true,
                        'predict_by_db' => true
                    ]
                );
            } catch (\Exception $e) {
                throw new MorphyException($e->getMessage(), [], $e);
            }
        }
    }

    /**
     * Sets a locale.
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = strtolower($locale);
        return $this;
    }

    /**
     * Sets a highlight template.
     * @param string $highlightTpl
     * @return $this
     */
    public function setHighlightTpl($highlightTpl)
    {
        $this->highlightTpl = $highlightTpl;
        return $this;
    }

    /**
     * Sets a path to dictionaries.
     * @param string $pathDict
     * @return $this
     */
    public function setPathDict($pathDict)
    {
        $this->pathDict = Alias::getAlias($pathDict);
        return $this;
    }

    /**
     * Returns base form on word.
     * @param string $content
     * @return string|null
     */
    public function baseForm($content)
    {
        if (empty($content)) {
            return null;
        }
        $words = preg_replace(['/\[.*\]/isu', '/[^\w\x7F-\xFF\s]/i'], "", trim($content));
        $words = preg_replace('/ +/', ' ', $words);
        //preg_match_all('/[a-zA-Z]+/iu',mb_strtoupper($words, CHARSET),$words_latin);
        //$words_latin = (is_array($words_latin) && count($words_latin) > 0) ? ' '.implode(' ', $words_latin[0]) : '';
        $words = preg_split('/\s|[,.:;!?"\'()]/', $words, -1, PREG_SPLIT_NO_EMPTY);
        $bulkWords = [];
        foreach ($words as $res) {
            if (mb_strlen($res, 'utf-8') > 2) {
                $bulkWords[] = mb_strtoupper($res, 'utf-8');
            }
        }
        //$this->_Morphy->getEncoding();
        $baseForm = static::$morphy->getBaseForm($bulkWords);
        if (is_array($baseForm) && count($baseForm)) {
            $dataWords = [];
            foreach ($baseForm as $key => $arr_res) {
                if (is_array($arr_res)) {
                    foreach ($arr_res as $val_res) {
                        if (mb_strlen($val_res, 'utf-8') > 2) {
                            $dataWords[$val_res] = 1;
                        }
                    }
                    /* те слова, что отсутсвуют в словаре */
                } else {
                    if (!empty($res) && mb_strlen($res, 'utf-8') > 2) {
                        $dataWords[$key] = 1;
                    }
                }
            }
            $words = implode(' ', array_keys($dataWords));
        }

        return $words;
    }

    protected static $content = [];

    /**
     * Returns word inflectional forms.
     * @param string $content
     * @return array
     */
    public function inflectionalForms($content)
    {
        if (empty($content)) {
            return null;
        }
        // optimization (Lazy loading)
        $hash = md5($content);
        if (isset(static::$content[$hash])) {
            return static::$content[$hash];
        }
        $content = preg_replace(
            ['/\[.*\]/isu', '/[^\w\x7F-\xFF\s]/isu', '/[\«\»\d]+/iu'],
            "",
            trim(strip_tags($content))
        );
        /**
         * trim twice spaces
         */
        $content = preg_replace('/ +/u', ' ', $content);
        //preg_match_all('/[a-zA-Z]+/iu',mb_strtoupper($str, CHARSET),$words_latin);
        //$words_latin = (is_array($words_latin) && count($words_latin) > 0) ? ' '.implode(' ', $words_latin[0]) : '';
        $words = preg_split('/\s|[,.:;!?"\'()]/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $bulkWords = [];
        foreach ($words as $res_words) {
            if (mb_strlen($res_words, 'utf-8') > 2) {
                $bulkWords[] = mb_strtoupper($res_words, 'utf-8');
            }
        }

        return static::$content[$hash] = static::$morphy->getAllForms($bulkWords);
        //return $res.$words_latin;
    }

    /**
     * Highlight words.
     * @param string $word query of search.
     * @param string $content content.
     * @return string
     */
    public function highlight($word, $content)
    {
        if (empty($word) || empty($content) || empty($this->highlightTpl)) {
            return $content;
        }
        $highlightWords = [];
        if ((!$words = $this->inflectionalForms($word)) || !is_array($words)) {
            return $content;
        }
        foreach ($words as $key => $res_words) {
            if (!$res_words) {
                $highlightWords[] = '/\b(' . $key . ')\b/isu';
            } else {
                foreach ($res_words as $res) {
                    $highlightWords[] = '/\b(' . $res . ')\b/isu';
                }
            }
        }

        return preg_replace(
            array_reverse($highlightWords),
            $this->highlightTpl,
            $content
        );
    }

    protected function normalizeLocale()
    {
        $this->locale = strtolower($this->locale);
        if ($this->locale == 'ru' || $this->locale == 'ru_ru') {
            return 'rus';
        }

        if ($this->locale == 'en' || $this->locale == 'en_en') {
            return 'eng';
        }

        return 'ger';
    }
}